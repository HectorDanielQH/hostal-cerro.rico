<?php

namespace App\Http\Controllers\AdminLte;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminLte\CheckInRequest;
use App\Http\Requests\AdminLte\CheckOutRequest;
use App\Http\Requests\AdminLte\UpdateRoomOperationalStatusRequest;
use App\Models\Customer;
use App\Models\HotelSetting;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Room;
use App\Services\Reception\DailyLedgerService;
use App\Services\HotelOperations\ReservationLedgerService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class FrontDeskController extends Controller
{
    private const RESERVATION_STATUSES = [
        Reservation::STATUS_PENDING => ['label' => 'Pendiente', 'badge' => 'badge text-bg-warning'],
        Reservation::STATUS_CONFIRMED => ['label' => 'Confirmada', 'badge' => 'badge text-bg-success'],
        Reservation::STATUS_CHECKED_IN => ['label' => 'Ocupada', 'badge' => 'badge text-bg-danger'],
        Reservation::STATUS_CHECKED_OUT => ['label' => 'Salida registrada', 'badge' => 'badge text-bg-secondary'],
        Reservation::STATUS_CANCELLED => ['label' => 'Cancelada', 'badge' => 'badge text-bg-secondary'],
        Reservation::STATUS_EXPIRED => ['label' => 'Expirada', 'badge' => 'badge text-bg-secondary'],
        Reservation::STATUS_NO_SHOW => ['label' => 'No se presento', 'badge' => 'badge text-bg-dark'],
    ];

    private const ROOM_STATUSES = [
        'available' => ['label' => 'Disponible', 'badge' => 'badge text-bg-success'],
        'occupied' => ['label' => 'Ocupada', 'badge' => 'badge text-bg-danger'],
        'reserved' => ['label' => 'Reservada', 'badge' => 'badge text-bg-warning'],
        'cleaning' => ['label' => 'En limpieza', 'badge' => 'badge text-bg-info'],
        'maintenance' => ['label' => 'En reparacion', 'badge' => 'badge text-bg-dark'],
    ];

    private const PAYMENT_STATUSES = [
        Payment::STATUS_PENDING => 'Pendiente',
        Payment::STATUS_CONFIRMED => 'Confirmado',
        Payment::STATUS_REJECTED => 'Rechazado',
        Payment::STATUS_CANCELLED => 'Anulado',
        Payment::STATUS_REFUNDED => 'Devuelto',
    ];

    public function index(DailyLedgerService $dailyLedgerService): View
    {
        abort_unless(auth()->user()->can('reservas.ver') || auth()->user()->can('habitaciones.ver'), 403);

        $hotelSetting = HotelSetting::current();

        return view('adminlte.front-desk.index', [
            'roomStatuses' => self::ROOM_STATUSES,
            'operationalRoomStatuses' => [
                'available' => self::ROOM_STATUSES['available']['label'],
                'reserved' => self::ROOM_STATUSES['reserved']['label'],
                'occupied' => self::ROOM_STATUSES['occupied']['label'],
                'cleaning' => self::ROOM_STATUSES['cleaning']['label'],
                'maintenance' => self::ROOM_STATUSES['maintenance']['label'],
            ],
            'dailyLedger' => $dailyLedgerService->build(Carbon::today()),
            'pendingPayments' => $this->pendingPaymentsForReception(),
            'supportedCurrencies' => $hotelSetting->supportedCurrencies(),
            'currencySymbols' => $hotelSetting->currencySymbols(),
        ]);
    }

    public function summary(): JsonResponse
    {
        abort_unless(auth()->user()->can('reservas.ver') || auth()->user()->can('habitaciones.ver'), 403);

        return response()->json([
            'arrivals_today' => Reservation::query()
                ->whereDate('check_in', Carbon::today())
                ->where('status', Reservation::STATUS_CONFIRMED)
                ->count(),
            'departures_today' => Reservation::query()
                ->whereDate('check_out', Carbon::today())
                ->where('status', Reservation::STATUS_CHECKED_IN)
                ->count(),
            'currently_occupied' => Reservation::query()
                ->where('status', Reservation::STATUS_CHECKED_IN)
                ->count(),
            'rooms_available' => Room::query()->where('status', 'available')->count(),
            'rooms_occupied' => Room::query()->where('status', 'occupied')->count(),
            'rooms_reserved' => Room::query()->where('status', 'reserved')->count(),
            'rooms_cleaning' => Room::query()->where('status', 'cleaning')->count(),
            'rooms_maintenance' => Room::query()->where('status', 'maintenance')->count(),
        ]);
    }

    public function arrivalsData(): JsonResponse
    {
        abort_unless(auth()->user()->can('reservas.ver'), 403);

        $query = Reservation::query()
            ->with(['customer', 'room.roomType'])
            ->whereDate('check_in', Carbon::today())
            ->where('status', Reservation::STATUS_CONFIRMED)
            ->select('reservations.*');

        return $this->reservationDataTable($query, true, false, false);
    }

    public function departuresData(): JsonResponse
    {
        abort_unless(auth()->user()->can('reservas.ver'), 403);

        $query = Reservation::query()
            ->with(['customer', 'room.roomType'])
            ->whereDate('check_out', Carbon::today())
            ->where('status', Reservation::STATUS_CHECKED_IN)
            ->select('reservations.*');

        return $this->reservationDataTable($query, false, true, false);
    }

    public function occupiedData(): JsonResponse
    {
        abort_unless(auth()->user()->can('reservas.ver'), 403);

        $query = Reservation::query()
            ->with(['customer', 'room.roomType'])
            ->where('status', Reservation::STATUS_CHECKED_IN)
            ->select('reservations.*');

        return $this->reservationDataTable($query, false, true, true);
    }

    public function roomsStatusData(): JsonResponse
    {
        abort_unless(auth()->user()->can('habitaciones.ver'), 403);

        $query = Room::query()
            ->with(['roomType', 'reservations.customer'])
            ->select('rooms.*');

        return DataTables::eloquent($query)
            ->addColumn('room_type_name', fn (Room $room): string => $room->roomType?->name ?? '-')
            ->addColumn('room_type_price_formatted', fn (Room $room): string => $room->roomType?->dualPriceLabel() ?? '-')
            ->addColumn('status_label', fn (Room $room): string => self::ROOM_STATUSES[$room->status]['label'] ?? ucfirst($room->status))
            ->addColumn('status_badge_class', fn (Room $room): string => self::ROOM_STATUSES[$room->status]['badge'] ?? 'badge text-bg-secondary')
            ->addColumn('current_reservation_label', fn (Room $room): string => $this->roomReservationLabel($room))
            ->addColumn('current_reservation_dates', fn (Room $room): string => $this->roomReservationDates($room))
            ->addColumn('active_label', fn (Room $room): string => $room->is_active ? 'Activo' : 'Inactivo')
            ->addColumn('can_change_status', fn (): bool => auth()->user()->can('habitaciones.estado'))
            ->addColumn('update_status_url', fn (Room $room): string => route('adminlte.front-desk.rooms.status', $room))
            ->toJson();
    }

    public function customerSummary(Customer $customer): JsonResponse
    {
        abort_unless(auth()->user()->can('reservas.ver') || auth()->user()->can('clientes.ver'), 403);

        $customer->load([
            'reservations' => fn ($query) => $query
                ->with(['room.roomType'])
                ->orderByDesc('check_in'),
            'payments' => fn ($query) => $query
                ->with('reservation')
                ->orderByDesc('created_at'),
        ]);

        return response()->json([
            'customer' => [
                'id' => $customer->id,
                'full_name' => $customer->full_name,
                'document_type' => $customer->document_type,
                'document_number' => $customer->document_number,
                'nationality' => $customer->nationality,
                'birth_date' => optional($customer->birth_date)?->toDateString(),
                'phone' => $customer->phone,
                'whatsapp' => $customer->whatsapp,
                'email' => $customer->email,
                'address' => $customer->address,
                'city' => $customer->city,
                'country' => $customer->country,
                'notes' => $customer->notes,
                'is_foreign' => (bool) $customer->is_foreign,
                'is_company' => (bool) $customer->is_company,
                'company_name' => $customer->company_name,
                'tax_number' => $customer->tax_number,
                'is_active' => (bool) $customer->is_active,
                'can_update' => auth()->user()->can('update', $customer),
                'update_url' => route('adminlte.customers.update', $customer),
            ],
            'summary' => [
                'reservations_count' => $customer->reservations->count(),
                'payments_count' => $customer->payments->count(),
                'confirmed_payments_count' => $customer->payments->where('status', Payment::STATUS_CONFIRMED)->count(),
                'pending_payments_count' => $customer->payments->where('status', Payment::STATUS_PENDING)->count(),
            ],
            'reservations' => $customer->reservations
                ->map(function (Reservation $reservation): array {
                    $ledger = app(ReservationLedgerService::class);
                    $displayCurrency = $ledger->displayCurrency($reservation);

                    return [
                    'id' => $reservation->id,
                    'code' => $reservation->code,
                    'room' => $reservation->room?->number,
                    'room_type' => $reservation->room?->roomType?->name,
                    'check_in' => optional($reservation->check_in)?->format('d/m/Y'),
                    'check_out' => optional($reservation->check_out)?->format('d/m/Y'),
                    'status' => $reservation->status,
                    'status_label' => self::RESERVATION_STATUSES[$reservation->status]['label'] ?? 'Sin estado',
                    'total' => (float) $reservation->total_amount,
                    'paid' => (float) $reservation->paid_amount,
                    'balance' => (float) $reservation->balance_amount,
                    'display_currency' => $displayCurrency,
                    'locked_payment_currency' => $ledger->lockedPaymentCurrency($reservation),
                    'display_total' => $ledger->amountFromBaseForDisplay($reservation, (float) $reservation->total_amount, $displayCurrency),
                    'display_paid' => $ledger->amountFromBaseForDisplay($reservation, (float) $reservation->paid_amount, $displayCurrency),
                    'display_balance' => $ledger->amountFromBaseForDisplay($reservation, (float) $reservation->balance_amount, $displayCurrency),
                ];
                })
                ->values(),
            'payments' => $customer->payments
                ->map(fn (Payment $payment): array => [
                    'reservation_id' => $payment->reservation_id,
                    'code' => $payment->code,
                    'reservation_code' => $payment->reservation?->code,
                    'amount' => (float) $payment->amount,
                    'currency' => $payment->currency ?: 'BOB',
                    'method' => $payment->payment_method,
                    'status' => $payment->status,
                    'status_label' => self::PAYMENT_STATUSES[$payment->status] ?? 'Sin estado',
                    'payment_date' => optional($payment->payment_date)?->format('d/m/Y'),
                    'reference' => $payment->reference_number,
                    'notes' => $payment->notes,
                    'receipt_url' => $payment->receipt_image ? route('adminlte.payments.receipt', $payment) : null,
                ])
                ->values(),
        ]);
    }

    public function updateReservationGuests(Reservation $reservation): JsonResponse
    {
        abort_unless(auth()->user()->can('reservas.editar') || auth()->user()->can('clientes.editar'), 403);

        $validated = request()->validate([
            'guests' => ['nullable', 'array', 'max:40'],
            'guests.*.full_name' => ['nullable', 'string', 'max:255'],
            'guests.*.document_type' => ['nullable', 'in:ci,passport,nit,other'],
            'guests.*.document_number' => ['nullable', 'string', 'max:100'],
            'guests.*.nationality' => ['nullable', 'string', 'max:100'],
            'guests.*.country' => ['nullable', 'string', 'max:100'],
            'guests.*.birth_date' => ['nullable', 'date', 'before:today'],
            'guests.*.relationship' => ['nullable', 'string', 'max:100'],
            'guests.*.notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $guests = collect($validated['guests'] ?? [])
            ->map(fn (array $guest): array => [
                'full_name' => trim((string) ($guest['full_name'] ?? '')),
                'document_type' => $guest['document_type'] ?? null,
                'document_number' => $guest['document_number'] ?? null,
                'nationality' => $guest['nationality'] ?? null,
                'country' => $guest['country'] ?? null,
                'birth_date' => $guest['birth_date'] ?? null,
                'relationship' => $guest['relationship'] ?? null,
                'notes' => $guest['notes'] ?? null,
            ])
            ->filter(fn (array $guest): bool => $guest['full_name'] !== '')
            ->values();

        DB::transaction(function () use ($reservation, $guests): void {
            $reservation->guests()->delete();
            $guests->each(fn (array $guest): \App\Models\ReservationGuest => $reservation->guests()->create($guest));
        });

        return response()->json([
            'message' => 'Acompanantes actualizados correctamente.',
        ]);
    }

    public function checkIn(CheckInRequest $request, Reservation $reservation): JsonResponse
    {
        abort_unless(auth()->user()->can('reservas.checkin'), 403);

        if ($shiftError = $this->currentReceptionShiftError()) {
            return $this->ledgerActionBlocked($shiftError);
        }

        if ($reservation->status !== Reservation::STATUS_CONFIRMED) {
            return $this->ledgerActionBlocked('Solo se puede registrar entrada a reservas confirmadas.');
        }

        $validated = $request->validated();
        $reservation->loadMissing('room');
        $room = $reservation->room;

        if (! $room) {
            return $this->ledgerActionBlocked('La reserva no tiene una habitacion asociada. Revise la reserva antes de registrar la entrada.');
        }

        if (in_array($room->status, ['cleaning', 'maintenance'], true)) {
            return $this->ledgerActionBlocked('La habitacion '.$room->number.' no esta disponible para entrada porque esta marcada como '.$this->roomStatusLabel($room->status).'.');
        }

        $occupiedByAnotherReservation = Reservation::query()
            ->where('room_id', $room->id)
            ->where('id', '!=', $reservation->id)
            ->where('status', Reservation::STATUS_CHECKED_IN)
            ->exists();

        if ($occupiedByAnotherReservation) {
            return $this->ledgerActionBlocked('La habitacion '.$room->number.' ya esta ocupada por otra reserva activa.');
        }

        DB::transaction(function () use ($reservation, $room, $validated): void {
            $reservation->update([
                'status' => Reservation::STATUS_CHECKED_IN,
                'checked_in_at' => now(),
                'internal_notes' => $this->appendOperationalNotes($reservation->internal_notes, 'Entrada', $validated['notes'] ?? null),
                'updated_by' => auth()->id(),
            ]);

            $room->update([
                'status' => 'occupied',
            ]);
        });

        return response()->json([
            'ok' => true,
            'message' => 'Entrada registrada correctamente.',
        ]);
    }

    public function checkOut(CheckOutRequest $request, Reservation $reservation): JsonResponse
    {
        abort_unless(auth()->user()->can('reservas.checkout'), 403);

        if ($shiftError = $this->currentReceptionShiftError()) {
            return $this->ledgerActionBlocked($shiftError);
        }

        if ($reservation->status !== Reservation::STATUS_CHECKED_IN) {
            return $this->ledgerActionBlocked('Solo se puede registrar salida a reservas con entrada activa.');
        }

        $validated = $request->validated();
        $forceCheckout = filter_var($validated['force_checkout'] ?? false, FILTER_VALIDATE_BOOL);

        if ((float) $reservation->balance_amount > 0 && ! $forceCheckout) {
            return $this->ledgerActionBlocked('La reserva tiene saldo pendiente. Confirme si desea registrar la salida de todas formas.');
        }

        DB::transaction(function () use ($reservation, $validated): void {
            $reservation->loadMissing('room');

            $reservation->update([
                'status' => Reservation::STATUS_CHECKED_OUT,
                'checked_out_at' => now(),
                'internal_notes' => $this->appendOperationalNotes($reservation->internal_notes, 'Salida', $validated['notes'] ?? null),
                'updated_by' => auth()->id(),
            ]);

            if ($reservation->room) {
                $reservation->room->update([
                    'status' => 'available',
                ]);
            }
        });

        return response()->json([
            'ok' => true,
            'message' => 'Salida registrada correctamente. La habitacion quedo disponible.',
        ]);
    }

    public function extendStay(Request $request, Reservation $reservation): JsonResponse
    {
        abort_unless(auth()->user()->can('reservas.editar'), 403);

        if ($shiftError = $this->currentReceptionShiftError()) {
            return $this->ledgerActionBlocked($shiftError);
        }

        if (! in_array($reservation->status, [Reservation::STATUS_CONFIRMED, Reservation::STATUS_CHECKED_IN], true)) {
            return $this->ledgerActionBlocked('Solo se puede ampliar una reserva confirmada u ocupada.');
        }

        $validated = $request->validate([
            'new_check_out' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [], [
            'new_check_out' => 'nueva fecha de salida',
            'notes' => 'observacion',
        ]);

        $reservation->loadMissing(['room.roomType']);

        if (! $reservation->room) {
            return $this->ledgerActionBlocked('La reserva no tiene una habitacion asociada.');
        }

        $currentCheckOut = Carbon::parse($reservation->check_out)->startOfDay();
        $newCheckOut = Carbon::parse($validated['new_check_out'])->startOfDay();

        if ($newCheckOut->lessThanOrEqualTo($currentCheckOut)) {
            return $this->ledgerActionBlocked('La nueva salida debe ser posterior a la salida actual: '.$currentCheckOut->format('d/m/Y').'.');
        }

        $hasConflict = Reservation::query()
            ->where('room_id', $reservation->room_id)
            ->where('id', '!=', $reservation->id)
            ->whereIn('status', Reservation::ACTIVE_STATUSES)
            ->where('check_in', '<', $newCheckOut->toDateString())
            ->where('check_out', '>', $currentCheckOut->toDateString())
            ->exists();

        if ($hasConflict) {
            return $this->ledgerActionBlocked('No se puede ampliar porque la habitacion ya tiene otra reserva en ese rango.');
        }

        $oldCheckOutLabel = optional($reservation->check_out)?->format('d/m/Y') ?? '-';
        $oldNights = (int) $reservation->nights;
        $confirmedPaidAmount = app(ReservationLedgerService::class)->confirmedAmount($reservation);

        DB::transaction(function () use ($reservation, $newCheckOut, $confirmedPaidAmount, $validated, $oldCheckOutLabel, $oldNights): void {
            $reservation->check_out = $newCheckOut->toDateString();
            $reservation->paid_amount = round($confirmedPaidAmount, 2);
            $reservation->recalculateTotals();
            $reservation->internal_notes = $this->appendOperationalNotes(
                $reservation->internal_notes,
                'Ampliacion de hospedaje',
                trim('Salida anterior: '.$oldCheckOutLabel.'. Nueva salida: '.$newCheckOut->format('d/m/Y').'. Noches anteriores: '.$oldNights.'. Noches actuales: '.$reservation->nights.'. '.($validated['notes'] ?? ''))
            );
            $reservation->updated_by = auth()->id();
            $reservation->save();
        });

        return response()->json([
            'ok' => true,
            'message' => 'Hospedaje ampliado correctamente hasta '.$newCheckOut->format('d/m/Y').'.',
        ]);
    }

    public function updateReservationDates(Request $request, Reservation $reservation): JsonResponse
    {
        abort_unless(auth()->user()->can('reservas.editar'), 403);

        if (! in_array($reservation->status, [Reservation::STATUS_PENDING, Reservation::STATUS_CONFIRMED], true)) {
            return $this->ledgerActionBlocked('Solo se pueden editar fechas de reservas pendientes o confirmadas.');
        }

        $validated = $request->validate([
            'check_in' => ['required', 'date'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [], [
            'check_in' => 'fecha de entrada',
            'check_out' => 'fecha de salida',
            'notes' => 'observacion',
        ]);

        $reservation->loadMissing(['room.roomType']);

        if (! $reservation->room) {
            return $this->ledgerActionBlocked('La reserva no tiene habitacion asociada.');
        }

        $checkIn = Carbon::parse($validated['check_in'])->startOfDay();
        $checkOut = Carbon::parse($validated['check_out'])->startOfDay();

        $hasConflict = Reservation::query()
            ->where('room_id', $reservation->room_id)
            ->where('id', '!=', $reservation->id)
            ->whereIn('status', Reservation::ACTIVE_STATUSES)
            ->where('check_in', '<', $checkOut->toDateString())
            ->where('check_out', '>', $checkIn->toDateString())
            ->exists();

        if ($hasConflict) {
            return $this->ledgerActionBlocked('No se puede guardar: esa habitacion ya tiene otra reserva en ese rango de fechas.');
        }

        $oldRange = optional($reservation->check_in)?->format('d/m/Y').' al '.optional($reservation->check_out)?->format('d/m/Y');

        DB::transaction(function () use ($reservation, $checkIn, $checkOut, $validated, $oldRange): void {
            $confirmedPaidAmount = app(ReservationLedgerService::class)->confirmedAmount($reservation);
            $reservation->check_in = $checkIn->toDateString();
            $reservation->check_out = $checkOut->toDateString();
            $reservation->paid_amount = round($confirmedPaidAmount, 2);
            $reservation->recalculateTotals();
            $reservation->internal_notes = $this->appendOperationalNotes(
                $reservation->internal_notes,
                'Cambio de fechas desde recepcion',
                trim('Rango anterior: '.$oldRange.'. Nuevo rango: '.$checkIn->format('d/m/Y').' al '.$checkOut->format('d/m/Y').'. '.($validated['notes'] ?? ''))
            );
            $reservation->updated_by = auth()->id();
            $reservation->save();
        });

        return response()->json([
            'ok' => true,
            'message' => 'Fechas de la reserva actualizadas correctamente.',
        ]);
    }

    public function reviewCancellation(Reservation $reservation): JsonResponse
    {
        abort_unless(auth()->user()->can('reservas.ver'), 403);

        if ($reservation->status !== Reservation::STATUS_CANCELLED || ! $reservation->cancelled_at) {
            return response()->json([
                'ok' => false,
                'message' => 'Solo se pueden revisar anulaciones solicitadas por el cliente.',
            ], 422);
        }

        if ($reservation->cancellation_reviewed_at) {
            return response()->json([
                'ok' => true,
                'message' => 'Esta anulacion ya estaba marcada como revisada.',
            ]);
        }

        $reservation->internal_notes = $this->appendOperationalNotes(
            $reservation->internal_notes,
            'Anulacion revisada por recepcion',
            'Se contacto o intento contactar al cliente y se reviso la politica de anulacion.'
        );
        $reservation->cancellation_reviewed_at = now();
        $reservation->cancellation_reviewed_by = auth()->id();
        $reservation->updated_by = auth()->id();
        $reservation->save();

        return response()->json([
            'ok' => true,
            'message' => 'Anulacion marcada como revisada. Queda guardada en el historial de la reserva.',
        ]);
    }

    public function updateRoomStatus(UpdateRoomOperationalStatusRequest $request, Room $room): JsonResponse
    {
        abort_unless(auth()->user()->can('habitaciones.estado'), 403);

        $validated = $request->validated();

        DB::transaction(function () use ($room, $validated): void {
            $hasCheckedInReservation = Reservation::query()
                ->where('room_id', $room->id)
                ->where('status', Reservation::STATUS_CHECKED_IN)
                ->exists();

            if ($validated['status'] === 'available' && $hasCheckedInReservation) {
                abort(422, 'No se puede marcar disponible una habitacion con una entrada activa.');
            }

            if ($validated['status'] !== 'occupied' && $hasCheckedInReservation) {
                abort(422, 'No se puede cambiar el estado de una habitacion con una entrada activa.');
            }

            $room->update([
                'status' => $validated['status'],
                'internal_notes' => $this->appendOperationalNotes($room->internal_notes, 'Estado operativo', $validated['notes'] ?? null),
            ]);
        });

        return response()->json([
            'message' => 'Estado de habitacion actualizado correctamente.',
        ]);
    }

    private function currentReceptionShiftError(): ?string
    {
        $user = auth()->user();

        if (! $user || ! $user->hasRole('receptionist') || $user->hasAnyRole(['admin', 'manager', 'general_manager'])) {
            return null;
        }

        $user->loadMissing('workShift');
        $shift = $user->workShift;

        if (! $shift || ! $shift->is_active) {
            return 'Tu usuario no tiene un turno activo asignado. Administracion debe revisar tu horario antes de registrar entradas o salidas.';
        }

        $now = now();
        $startTime = strlen((string) $shift->starts_at) === 5 ? $shift->starts_at.':00' : substr((string) $shift->starts_at, 0, 8);
        $endTime = strlen((string) $shift->ends_at) === 5 ? $shift->ends_at.':00' : substr((string) $shift->ends_at, 0, 8);
        $start = Carbon::createFromFormat('H:i:s', $startTime ?: '00:00:00')->setDateFrom($now);
        $end = Carbon::createFromFormat('H:i:s', $endTime ?: '23:59:59')->setDateFrom($now);

        if ($end->lessThanOrEqualTo($start)) {
            $end->addDay();

            if ($now->lessThan($start)) {
                $start->subDay();
                $end->subDay();
            }
        }

        if ($now->lt($start) || $now->gt($end)) {
            return 'No estas dentro de tu turno configurado: '.$shift->name.' ('.$shift->scheduleLabel().'). Hora actual del sistema: '.$now->format('H:i').'.';
        }

        return null;
    }

    private function ledgerActionBlocked(string $message): JsonResponse
    {
        return response()->json([
            'ok' => false,
            'message' => $message,
        ]);
    }

    private function roomStatusLabel(?string $status): string
    {
        return strtolower(self::ROOM_STATUSES[$status]['label'] ?? 'no disponible');
    }

    private function reservationDataTable($query, bool $includeCheckIn, bool $includeCheckOut, bool $compact): JsonResponse
    {
        return DataTables::eloquent($query)
            ->addColumn('customer_name', fn (Reservation $reservation): string => $reservation->customer?->full_name ?? '-')
            ->addColumn('customer_document', fn (Reservation $reservation): string => $reservation->customer?->document_number ?? '')
            ->addColumn('room_number', fn (Reservation $reservation): string => $reservation->room?->number ?? '-')
            ->addColumn('room_type_name', fn (Reservation $reservation): string => $reservation->room?->roomType?->name ?? '-')
            ->addColumn('check_in_formatted', fn (Reservation $reservation): string => optional($reservation->check_in)?->format('d/m/Y') ?? '-')
            ->addColumn('check_out_formatted', fn (Reservation $reservation): string => optional($reservation->check_out)?->format('d/m/Y') ?? '-')
            ->addColumn('guests_summary', fn (Reservation $reservation): string => sprintf('%d adulto(s) / %d nino(s)', (int) $reservation->adults, (int) $reservation->children))
            ->addColumn('total_amount_formatted', fn (Reservation $reservation): string => $this->formatMoney((float) $reservation->total_amount))
            ->addColumn('paid_amount_formatted', fn (Reservation $reservation): string => $this->formatMoney((float) $reservation->paid_amount))
            ->addColumn('balance_amount_formatted', fn (Reservation $reservation): string => $this->formatMoney((float) $reservation->balance_amount))
            ->addColumn('status_label', fn (Reservation $reservation): string => self::RESERVATION_STATUSES[$reservation->status]['label'] ?? ucfirst($reservation->status))
            ->addColumn('status_badge_class', fn (Reservation $reservation): string => self::RESERVATION_STATUSES[$reservation->status]['badge'] ?? 'badge text-bg-secondary')
            ->addColumn('can_checkin', fn (Reservation $reservation): bool => $includeCheckIn && auth()->user()->can('reservas.checkin') && $reservation->status === Reservation::STATUS_CONFIRMED)
            ->addColumn('can_checkout', fn (Reservation $reservation): bool => $includeCheckOut && auth()->user()->can('reservas.checkout') && $reservation->status === Reservation::STATUS_CHECKED_IN)
            ->addColumn('checkin_url', fn (Reservation $reservation): string => route('adminlte.front-desk.check-in', $reservation))
            ->addColumn('checkout_url', fn (Reservation $reservation): string => route('adminlte.front-desk.check-out', $reservation))
            ->addColumn('compact_view', fn (): bool => $compact)
            ->toJson();
    }

    private function appendOperationalNotes(?string $currentNotes, string $context, ?string $notes): ?string
    {
        $notes = trim((string) $notes);
        if ($notes === '') {
            return $currentNotes;
        }

        $entry = sprintf('[%s] %s: %s', now()->format('d/m/Y H:i'), $context, $notes);

        return trim((string) $currentNotes) !== ''
            ? trim((string) $currentNotes).PHP_EOL.$entry
            : $entry;
    }

    private function roomReservationLabel(Room $room): string
    {
        $reservation = $this->roomCurrentReservation($room);

        if (! $reservation) {
            return '-';
        }

        $customer = $reservation->customer?->full_name ?? 'Sin cliente';

        return $reservation->code.' - '.$customer;
    }

    private function roomReservationDates(Room $room): string
    {
        $reservation = $this->roomCurrentReservation($room);

        if (! $reservation) {
            return '-';
        }

        return sprintf(
            '%s al %s',
            optional($reservation->check_in)?->format('d/m/Y') ?? '-',
            optional($reservation->check_out)?->format('d/m/Y') ?? '-'
        );
    }

    private function roomCurrentReservation(Room $room): ?Reservation
    {
        return $room->reservations
            ->whereIn('status', [Reservation::STATUS_CONFIRMED, Reservation::STATUS_CHECKED_IN])
            ->sortBy(fn (Reservation $reservation): string => optional($reservation->check_in)?->toDateString() ?? '9999-12-31')
            ->first();
    }

    private function formatMoney(float $amount): string
    {
        return 'Bs. '.number_format($amount, 2, '.', '');
    }

    private function pendingPaymentsForReception()
    {
        if (! auth()->user()->can('pagos.ver')) {
            return collect();
        }

        return Payment::query()
            ->with(['reservation.room', 'customer'])
            ->where('status', Payment::STATUS_PENDING)
            ->orderByDesc('created_at')
            ->limit(6)
            ->get();
    }
}
