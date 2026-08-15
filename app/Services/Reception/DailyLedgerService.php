<?php

namespace App\Services\Reception;

use App\Models\Reservation;
use App\Models\Room;
use App\Services\HotelOperations\ReservationLedgerService;
use App\Support\DatabaseDialect;
use Illuminate\Support\Carbon;

class DailyLedgerService
{
    /**
     * Build a reception notebook-style view: one row per active room.
     *
     * @return array{date: Carbon, rows: \Illuminate\Support\Collection<int, array<string, mixed>>, summary: array<string, int>}
     */
    public function build(?Carbon $date = null): array
    {
        $date = ($date ?? Carbon::today())->startOfDay();

        $rooms = Room::query()
            ->with([
                'roomType',
                'reservations' => fn ($query) => $query
                    ->with(['customer', 'createdBy', 'cancellationReviewedBy', 'payments', 'guests'])
                    ->orderByDesc('check_in'),
            ])
            ->where('is_active', true)
            ->orderByRaw(DatabaseDialect::naturalRoomOrderExpression())
            ->get();

        $rows = $rooms->map(function (Room $room) use ($date): array {
            $reservation = $room->reservations
                ->filter(fn (Reservation $reservation): bool => $this->reservationShouldAppearInLedger($reservation, $date))
                ->sortBy(fn (Reservation $reservation): int => match ($reservation->status) {
                    Reservation::STATUS_CHECKED_IN => 1,
                    Reservation::STATUS_CONFIRMED => 2,
                    Reservation::STATUS_PENDING => 3,
                    default => 9,
                })
                ->first();

            return $this->rowFor($room, $reservation, $date);
        });

        return [
            'date' => $date,
            'rows' => $rows,
            'stay_alerts' => $this->stayAlerts($rooms, $rows, $date),
            'summary' => [
                'total_rooms' => $rows->count(),
                'available' => $rows->where('ledger_status', 'available')->count(),
                'reserved' => $rows->where('ledger_status', 'reserved')->count(),
                'occupied' => $rows->where('ledger_status', 'occupied')->count(),
                'cleaning' => $rows->where('ledger_status', 'cleaning')->count(),
                'maintenance' => $rows->where('ledger_status', 'maintenance')->count(),
                'pending' => $rows->where('reservation_status', Reservation::STATUS_PENDING)->count(),
                'arrivals' => $rows->where('is_arrival_today', true)->count(),
                'departures' => $rows->where('is_departure_today', true)->count(),
            ],
        ];
    }

    private function reservationShouldAppearInLedger(Reservation $reservation, Carbon $date): bool
    {
        if (! in_array($reservation->status, Reservation::ACTIVE_STATUSES, true)) {
            return false;
        }

        if ($reservation->status === Reservation::STATUS_CHECKED_IN) {
            return $reservation->check_in <= $date;
        }

        return $reservation->check_in <= $date && $reservation->check_out >= $date;
    }

    private function rowFor(Room $room, ?Reservation $reservation, Carbon $date): array
    {
        $customer = $reservation?->customer;
        $nights = $reservation ? max((int) $reservation->nights, 1) : null;
        $people = $reservation ? (int) $reservation->adults + (int) $reservation->children : null;
        $ledgerStatus = $this->ledgerStatus($room, $reservation);
        $roomReservations = $this->roomReservationsForModal($room);
        $roomPayments = $this->roomPaymentsForModal($roomReservations);

        return [
            'room' => $room,
            'reservation' => $reservation,
            'room_number' => $room->number,
            'room_type' => $room->roomType?->name ?? 'Sin tipo',
            'room_status' => $room->status,
            'room_status_label' => $this->roomStatusLabel((string) $room->status),
            'ledger_status' => $ledgerStatus,
            'ledger_status_label' => $this->ledgerStatusLabel($ledgerStatus),
            'ledger_status_class' => $this->ledgerStatusClass($ledgerStatus),
            'status_options' => $this->statusOptions(),
            'customer_name' => $customer?->full_name,
            'customer_phone' => $customer?->phone ?: $customer?->whatsapp,
            'customer_city' => $customer?->city,
            'customer_country' => $customer?->country ?: $customer?->nationality,
            'people' => $people,
            'nights' => $nights,
            'confirmed_label' => $reservation ? ($reservation->status === Reservation::STATUS_PENDING ? 'NO' : 'SI') : '',
            'reservation_status' => $reservation?->status,
            'reservation_status_label' => $reservation ? $this->reservationStatusLabel($reservation->status) : 'Libre',
            'date_range' => $reservation
                ? sprintf('%s al %s', optional($reservation->check_in)->format('d/m'), optional($reservation->check_out)->format('d/m'))
                : '',
            'is_arrival_today' => $reservation && optional($reservation->check_in)->isSameDay($date),
            'is_departure_today' => $reservation && optional($reservation->check_out)->isSameDay($date),
            'is_departure_tomorrow' => $reservation && optional($reservation->check_out)->isSameDay($date->copy()->addDay()),
            'is_checkout_overdue' => $reservation
                && $reservation->status === Reservation::STATUS_CHECKED_IN
                && $reservation->check_out
                && $reservation->check_out->lt($date),
            'days_overdue' => $reservation && $reservation->check_out && $reservation->check_out->lt($date)
                ? $reservation->check_out->diffInDays($date)
                : 0,
            'notes' => $reservation?->special_requests ?: $reservation?->internal_notes ?: $room->internal_notes,
            'initials' => $reservation?->createdBy?->name ? $this->initials($reservation->createdBy->name) : '',
            'balance' => $reservation ? (float) $reservation->balance_amount : null,
            'room_reservations' => $roomReservations,
            'room_payments' => $roomPayments,
            'can_checkin' => $reservation
                && $reservation->status === Reservation::STATUS_CONFIRMED
                && auth()->user()?->can('reservas.checkin'),
            'can_checkout' => $reservation
                && $reservation->status === Reservation::STATUS_CHECKED_IN
                && auth()->user()?->can('reservas.checkout'),
            'can_change_room_status' => auth()->user()?->can('habitaciones.estado'),
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Room>  $rooms
     * @param  \Illuminate\Support\Collection<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function stayAlerts($rooms, $rows, Carbon $date): array
    {
        $departureAlerts = $rows
            ->filter(fn (array $row): bool => $row['reservation'] instanceof Reservation
                && $row['reservation']->status === Reservation::STATUS_CHECKED_IN
                && (
                    $row['is_checkout_overdue']
                    || $row['is_departure_today']
                    || $row['is_departure_tomorrow']
                ))
            ->map(function (array $row) use ($date): array {
                /** @var Reservation $reservation */
                $reservation = $row['reservation'];
                $checkOut = $reservation->check_out?->copy()->startOfDay();
                $severity = 'warning';
                $title = 'Sale manana';
                $message = 'Confirmar si ampliara hospedaje o si dejara la habitacion libre.';

                if ($checkOut && $checkOut->lt($date)) {
                    $severity = 'danger';
                    $days = max($checkOut->diffInDays($date), 1);
                    $title = 'Hospedaje excedido';
                    $message = 'Cuidado: ya paso '.$days.' dia(s) desde la salida planificada.';
                } elseif ($checkOut && $checkOut->isSameDay($date)) {
                    $severity = 'urgent';
                    $title = 'Sale hoy';
                    $message = 'Cuidado: revisar salida o ampliacion antes de liberar la habitacion.';
                }

                return [
                    'priority' => $severity === 'danger' ? 1 : ($severity === 'urgent' ? 2 : 3),
                    'type' => 'departure',
                    'severity' => $severity,
                    'title' => $title,
                    'message' => $message,
                    'room_number' => $row['room_number'],
                    'room_type' => $row['room_type'],
                    'customer_name' => $row['customer_name'],
                    'customer_phone' => $row['customer_phone'],
                    'reservation_code' => $reservation->code,
                    'check_in' => optional($reservation->check_in)?->format('d/m/Y'),
                    'check_out' => optional($reservation->check_out)?->format('d/m/Y'),
                    'balance' => (float) $reservation->balance_amount,
                ];
            })
            ->values();

        $dateTo = $date->copy()->addDays(7);
        $arrivalAlerts = $rooms
            ->flatMap(fn (Room $room) => $room->reservations->map(fn (Reservation $reservation): array => [
                'room' => $room,
                'reservation' => $reservation,
            ]))
            ->filter(function (array $item) use ($date, $dateTo): bool {
                /** @var Reservation $reservation */
                $reservation = $item['reservation'];

                return in_array($reservation->status, [Reservation::STATUS_PENDING, Reservation::STATUS_CONFIRMED], true)
                    && $reservation->check_in
                    && $reservation->check_in->betweenIncluded($date, $dateTo);
            })
            ->map(function (array $item) use ($date): array {
                /** @var Room $room */
                $room = $item['room'];
                /** @var Reservation $reservation */
                $reservation = $item['reservation'];
                $daysUntil = max($date->diffInDays($reservation->check_in), 0);
                $severity = $reservation->status === Reservation::STATUS_PENDING ? 'arrival-pending' : 'arrival';
                $title = $daysUntil === 0 ? 'Entra hoy' : 'Proxima entrada';
                $message = $reservation->status === Reservation::STATUS_PENDING
                    ? 'Reserva aun pendiente: confirmar anticipo o datos antes de la llegada.'
                    : 'Preparar habitacion y revisar datos antes de la llegada.';

                return [
                    'priority' => $daysUntil === 0 ? 2 : 4,
                    'type' => 'arrival',
                    'severity' => $severity,
                    'title' => $title,
                    'message' => $message,
                    'room_number' => $room->number,
                    'room_id' => $room->id,
                    'room_type' => $room->roomType?->name ?? 'Sin tipo',
                    'customer_name' => $reservation->customer?->full_name ?? 'Sin cliente',
                    'customer_phone' => $reservation->customer?->phone ?: $reservation->customer?->whatsapp,
                    'reservation_code' => $reservation->code,
                    'reservation_id' => $reservation->id,
                    'check_in' => optional($reservation->check_in)?->format('d/m/Y'),
                    'check_out' => optional($reservation->check_out)?->format('d/m/Y'),
                    'balance' => (float) $reservation->balance_amount,
                    'days_until' => $daysUntil,
                ];
            });

        $recentCancellationLimit = $date->copy()->subDays(7)->startOfDay();
        $cancellationAlerts = $rooms
            ->flatMap(fn (Room $room) => $room->reservations->map(fn (Reservation $reservation): array => [
                'room' => $room,
                'reservation' => $reservation,
            ]))
            ->filter(function (array $item) use ($date, $recentCancellationLimit): bool {
                /** @var Reservation $reservation */
                $reservation = $item['reservation'];

                return $reservation->status === Reservation::STATUS_CANCELLED
                    && $reservation->source === 'website'
                    && $reservation->cancelled_at
                    && ! $reservation->cancellation_reviewed_at
                    && (
                        $reservation->cancelled_at->greaterThanOrEqualTo($recentCancellationLimit)
                        || ($reservation->check_in && $reservation->check_in->greaterThanOrEqualTo($date))
                    );
            })
            ->map(function (array $item): array {
                /** @var Room $room */
                $room = $item['room'];
                /** @var Reservation $reservation */
                $reservation = $item['reservation'];

                return [
                    'priority' => 1,
                    'type' => 'cancellation',
                    'severity' => 'cancellation',
                    'title' => 'Anulacion solicitada',
                    'message' => $reservation->cancellation_reason
                        ? 'Motivo del cliente: '.$reservation->cancellation_reason
                        : 'El cliente anulo desde su panel. Revisar politica de 5 dias habiles y devolucion si corresponde.',
                    'room_number' => $room->number,
                    'room_id' => $room->id,
                    'room_type' => $room->roomType?->name ?? 'Sin tipo',
                    'customer_name' => $reservation->customer?->full_name ?? 'Sin cliente',
                    'customer_phone' => $reservation->customer?->phone ?: $reservation->customer?->whatsapp,
                    'reservation_code' => $reservation->code,
                    'reservation_id' => $reservation->id,
                    'cancellation_review_url' => route('adminlte.front-desk.reservations.cancellation-review', $reservation),
                    'check_in' => optional($reservation->check_in)?->format('d/m/Y'),
                    'check_out' => optional($reservation->check_out)?->format('d/m/Y'),
                    'cancelled_at' => optional($reservation->cancelled_at)?->format('d/m/Y H:i'),
                    'balance' => (float) $reservation->balance_amount,
                ];
            });

        return $departureAlerts
            ->concat($arrivalAlerts)
            ->concat($cancellationAlerts)
            ->sortBy([
                ['priority', 'asc'],
                ['check_in', 'asc'],
                ['check_out', 'asc'],
                ['room_number', 'asc'],
            ])
            ->values()
            ->all();
    }

    private function ledgerStatus(Room $room, ?Reservation $reservation): string
    {
        if (in_array($room->status, ['cleaning', 'maintenance'], true) && ! $reservation) {
            return $room->status;
        }

        if ($reservation?->status === Reservation::STATUS_CHECKED_IN || $room->status === 'occupied') {
            return 'occupied';
        }

        if ($reservation || $room->status === 'reserved') {
            return 'reserved';
        }

        return 'available';
    }

    private function ledgerStatusLabel(string $status): string
    {
        return match ($status) {
            'occupied' => 'Ocupada',
            'reserved' => 'Reservada',
            'cleaning' => 'En limpieza',
            'maintenance' => 'En reparacion',
            default => 'Disponible',
        };
    }

    private function ledgerStatusClass(string $status): string
    {
        return match ($status) {
            'occupied' => 'ledger-status--occupied',
            'reserved' => 'ledger-status--reserved',
            'cleaning' => 'ledger-status--cleaning',
            'maintenance' => 'ledger-status--maintenance',
            default => 'ledger-status--available',
        };
    }

    private function roomStatusLabel(string $status): string
    {
        return match ($status) {
            'occupied' => 'Ocupada',
            'reserved' => 'Reservada',
            'cleaning' => 'En limpieza',
            'maintenance' => 'En reparacion',
            default => 'Disponible',
        };
    }

    private function statusOptions(): array
    {
        return [
            ['value' => 'available', 'label' => 'Disponible'],
            ['value' => 'reserved', 'label' => 'Reservada'],
            ['value' => 'occupied', 'label' => 'Ocupada'],
            ['value' => 'cleaning', 'label' => 'En limpieza'],
            ['value' => 'maintenance', 'label' => 'En reparacion'],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function roomReservationsForModal(Room $room): array
    {
        $ledger = app(ReservationLedgerService::class);

        return $room->reservations
            ->sortBy(fn (Reservation $reservation): string => optional($reservation->check_in)?->toDateString() ?? '9999-12-31')
            ->map(function (Reservation $reservation) use ($ledger, $room): array {
                $displayCurrency = $ledger->displayCurrency($reservation);
                $realCheckOutDate = $reservation->status === Reservation::STATUS_CHECKED_OUT
                    ? $reservation->checked_out_at
                    : null;
                $calendarCheckOutDate = $realCheckOutDate ?: $reservation->check_out;
                $conflicts = $room->reservations
                    ->filter(fn (Reservation $candidate): bool => $candidate->id !== $reservation->id
                        && in_array($candidate->status, Reservation::ACTIVE_STATUSES, true)
                        && $candidate->check_in
                        && $candidate->check_out
                        && $reservation->check_in
                        && $reservation->check_out
                        && $candidate->check_in->lt($reservation->check_out)
                        && $candidate->check_out->gt($reservation->check_in))
                    ->map(fn (Reservation $candidate): array => [
                        'id' => $candidate->id,
                        'code' => $candidate->code,
                        'customer' => $candidate->customer?->full_name ?? 'Sin cliente',
                        'check_in' => optional($candidate->check_in)?->format('d/m/Y'),
                        'check_out' => optional($candidate->check_out)?->format('d/m/Y'),
                        'status_label' => $this->reservationStatusLabel($candidate->status),
                    ])
                    ->values()
                    ->all();

                return [
                'id' => $reservation->id,
                'code' => $reservation->code,
                'customer_id' => $reservation->customer_id,
                'customer' => $reservation->customer?->full_name ?? 'Sin cliente',
                'phone' => $reservation->customer?->phone ?: $reservation->customer?->whatsapp,
                'whatsapp' => $reservation->customer?->whatsapp,
                'email' => $reservation->customer?->email,
                'document_type' => $reservation->customer?->document_type,
                'document_number' => $reservation->customer?->document_number,
                'nationality' => $reservation->customer?->nationality,
                'city' => $reservation->customer?->city,
                'country' => $reservation->customer?->country,
                'customer_notes' => $reservation->customer?->notes,
                'people' => (int) $reservation->adults + (int) $reservation->children,
                'nights' => (int) $reservation->nights,
                'check_in' => optional($reservation->check_in)?->format('d/m/Y'),
                'check_out' => optional($reservation->check_out)?->format('d/m/Y'),
                'check_in_iso' => optional($reservation->check_in)?->toDateString(),
                'check_out_iso' => optional($reservation->check_out)?->toDateString(),
                'checked_in_at' => optional($reservation->checked_in_at)?->format('d/m/Y H:i'),
                'checked_out_at' => optional($reservation->checked_out_at)?->format('d/m/Y H:i'),
                'cancelled_at' => optional($reservation->cancelled_at)?->format('d/m/Y H:i'),
                'cancellation_reason' => $reservation->cancellation_reason,
                'cancellation_reviewed_at' => optional($reservation->cancellation_reviewed_at)?->format('d/m/Y H:i'),
                'cancellation_reviewed_by' => $reservation->cancellationReviewedBy?->name,
                'checked_out_date' => optional($reservation->checked_out_at)?->format('d/m/Y'),
                'checked_out_iso' => optional($reservation->checked_out_at)?->toDateString(),
                'calendar_check_in_iso' => optional($reservation->check_in)?->toDateString(),
                'calendar_check_out_iso' => optional($calendarCheckOutDate)?->toDateString(),
                'is_early_checkout' => $realCheckOutDate && $reservation->check_out && $realCheckOutDate->toDateString() < $reservation->check_out->toDateString(),
                'status' => $reservation->status,
                'status_label' => $this->reservationStatusLabel($reservation->status),
                'source' => $reservation->source,
                'is_online_request' => $reservation->source === 'website' && $reservation->status === Reservation::STATUS_PENDING,
                'total' => (float) $reservation->total_amount,
                'paid' => (float) $reservation->paid_amount,
                'balance' => (float) $reservation->balance_amount,
                'display_currency' => $displayCurrency,
                'locked_payment_currency' => $ledger->lockedPaymentCurrency($reservation),
                'display_total' => $ledger->amountFromBaseForDisplay($reservation, (float) $reservation->total_amount, $displayCurrency),
                'display_paid' => $ledger->amountFromBaseForDisplay($reservation, (float) $reservation->paid_amount, $displayCurrency),
                'display_balance' => $ledger->amountFromBaseForDisplay($reservation, (float) $reservation->balance_amount, $displayCurrency),
                'notes' => $reservation->special_requests ?: $reservation->internal_notes,
                'payments_count' => $reservation->payments->count(),
                'pending_payments_count' => $reservation->payments->where('status', \App\Models\Payment::STATUS_PENDING)->count(),
                'confirmed_payments_count' => $reservation->payments->where('status', \App\Models\Payment::STATUS_CONFIRMED)->count(),
                'deposit_percentage' => $reservation->normalizedDepositPercentage(),
                'deposit_required' => (float) $reservation->deposit_amount_required,
                'deposit_pending' => $reservation->depositAmountPending(),
                'has_required_deposit' => $reservation->hasRequiredDeposit(),
                'has_conflicts' => count($conflicts) > 0,
                'conflicts' => $conflicts,
                'preferred_payment_method' => $reservation->preferred_payment_method,
                'customer_summary_url' => $reservation->customer_id ? route('adminlte.front-desk.customers.summary', $reservation->customer_id) : null,
                'customer_update_url' => $reservation->customer_id ? route('adminlte.customers.update', $reservation->customer_id) : null,
                'can_update_customer' => $reservation->customer ? auth()->user()?->can('update', $reservation->customer) : false,
                'can_update_reservation' => auth()->user()?->can('update', $reservation),
                'can_confirm_reservation' => auth()->user()?->can('confirm', $reservation) && $reservation->canBeConfirmed(),
                'can_cancel_reservation' => auth()->user()?->can('cancel', $reservation) && $reservation->canBeCancelled(),
                'can_checkin' => $reservation->status === Reservation::STATUS_CONFIRMED && auth()->user()?->can('reservas.checkin'),
                'can_checkout' => $reservation->status === Reservation::STATUS_CHECKED_IN && auth()->user()?->can('reservas.checkout'),
                'can_extend' => in_array($reservation->status, [Reservation::STATUS_CONFIRMED, Reservation::STATUS_CHECKED_IN], true) && auth()->user()?->can('reservas.editar'),
                'update_url' => route('adminlte.reservations.update', $reservation),
                'dates_update_url' => route('adminlte.front-desk.reservations.dates', $reservation),
                'confirm_url' => route('adminlte.reservations.confirm', $reservation),
                'cancel_url' => route('adminlte.reservations.cancel', $reservation),
                'checkin_url' => route('adminlte.front-desk.check-in', $reservation),
                'checkout_url' => route('adminlte.front-desk.check-out', $reservation),
                'extend_url' => route('adminlte.front-desk.reservations.extend', $reservation),
                'guest_update_url' => route('adminlte.front-desk.reservations.guests', $reservation),
                'cancellation_review_url' => route('adminlte.front-desk.reservations.cancellation-review', $reservation),
                'payments' => $reservation->payments
                    ->sortByDesc('id')
                    ->map(fn (\App\Models\Payment $payment): array => [
                        'id' => $payment->id,
                        'code' => $payment->code,
                        'amount' => (float) $payment->amount,
                        'currency' => $payment->currency ?: 'BOB',
                        'amount_formatted' => $this->formatMoneyByCurrency((float) $payment->amount, $payment->currency ?: 'BOB'),
                        'method' => $payment->payment_method,
                        'status' => $payment->status,
                        'status_label' => $this->paymentStatusLabel($payment->status),
                        'payment_date' => optional($payment->payment_date)?->format('d/m/Y'),
                        'reference' => $payment->reference_number,
                        'notes' => $payment->notes,
                        'rejection_reason' => $payment->rejection_reason,
                        'receipt_url' => $payment->receipt_image ? route('adminlte.payments.receipt', $payment) : null,
                        'confirm_url' => route('adminlte.payments.confirm', $payment),
                        'reject_url' => route('adminlte.payments.reject', $payment),
                        'can_confirm' => auth()->user()?->can('confirm', $payment) && $payment->canBeConfirmed(),
                        'can_reject' => auth()->user()?->can('reject', $payment) && $payment->canBeRejected(),
                    ])
                    ->values()
                    ->all(),
                'guests' => $reservation->guests
                    ->map(fn ($guest): array => [
                        'id' => $guest->id,
                        'full_name' => $guest->full_name,
                        'document_type' => $guest->document_type,
                        'document_number' => $guest->document_number,
                        'nationality' => $guest->nationality,
                        'country' => $guest->country,
                        'birth_date' => optional($guest->birth_date)?->toDateString(),
                        'relationship' => $guest->relationship,
                        'notes' => $guest->notes,
                    ])
                    ->values()
                    ->all(),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $reservations
     * @return array<int, array<string, mixed>>
     */
    private function roomPaymentsForModal(array $reservations): array
    {
        $reservationIds = collect($reservations)->pluck('id')->all();

        if ($reservationIds === []) {
            return [];
        }

        return \App\Models\Payment::query()
            ->with(['reservation.customer'])
            ->whereIn('reservation_id', $reservationIds)
            ->orderByRaw(DatabaseDialect::orderByListExpression('status', ['pending', 'rejected', 'confirmed', 'cancelled', 'refunded']))
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (\App\Models\Payment $payment): array => [
                'id' => $payment->id,
                'code' => $payment->code,
                'reservation_code' => $payment->reservation?->code,
                'customer' => $payment->customer?->full_name ?? $payment->reservation?->customer?->full_name ?? 'Cliente',
                'amount' => (float) $payment->amount,
                'currency' => $payment->currency ?: 'BOB',
                'method' => $payment->payment_method,
                'status' => $payment->status,
                'status_label' => $this->paymentStatusLabel($payment->status),
                'payment_date' => optional($payment->payment_date)?->format('d/m/Y'),
                'reference' => $payment->reference_number,
                'notes' => $payment->notes,
                'receipt_url' => $payment->receipt_image ? route('adminlte.payments.receipt', $payment) : null,
                'confirm_url' => route('adminlte.payments.confirm', $payment),
                'reject_url' => route('adminlte.payments.reject', $payment),
                'can_confirm' => auth()->user()?->can('confirm', $payment) && $payment->canBeConfirmed(),
                'can_reject' => auth()->user()?->can('reject', $payment) && $payment->canBeRejected(),
            ])
            ->values()
            ->all();
    }

    private function paymentStatusLabel(string $status): string
    {
        return match ($status) {
            \App\Models\Payment::STATUS_PENDING => 'Pendiente',
            \App\Models\Payment::STATUS_CONFIRMED => 'Confirmado',
            \App\Models\Payment::STATUS_REJECTED => 'Rechazado',
            \App\Models\Payment::STATUS_CANCELLED => 'Anulado',
            \App\Models\Payment::STATUS_REFUNDED => 'Devuelto',
            default => ucfirst($status),
        };
    }

    private function formatMoneyByCurrency(float $amount, ?string $currency = 'BOB'): string
    {
        return (strtoupper((string) $currency) === 'USD' ? '$us ' : 'Bs. ').number_format($amount, 2, '.', '');
    }

    private function reservationStatusLabel(string $status): string
    {
        return match ($status) {
            Reservation::STATUS_PENDING => 'Pendiente',
            Reservation::STATUS_CONFIRMED => 'Confirmada',
            Reservation::STATUS_CHECKED_IN => 'Ocupada',
            Reservation::STATUS_CHECKED_OUT => 'Salida registrada',
            Reservation::STATUS_CANCELLED => 'Cancelada',
            Reservation::STATUS_EXPIRED => 'Vencida',
            Reservation::STATUS_NO_SHOW => 'No se presento',
            default => 'Sin estado',
        };
    }

    private function initials(string $name): string
    {
        return collect(preg_split('/\s+/', trim($name)) ?: [])
            ->filter()
            ->take(2)
            ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');
    }
}
