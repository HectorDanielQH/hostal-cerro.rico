<?php

namespace App\Http\Controllers\AdminLte;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminLte\CheckInRequest;
use App\Http\Requests\AdminLte\CheckOutRequest;
use App\Http\Requests\AdminLte\UpdateRoomOperationalStatusRequest;
use App\Models\Reservation;
use App\Models\Room;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class FrontDeskController extends Controller
{
    private const RESERVATION_STATUSES = [
        Reservation::STATUS_PENDING => ['label' => 'Pendiente', 'badge' => 'badge text-bg-warning'],
        Reservation::STATUS_CONFIRMED => ['label' => 'Confirmada', 'badge' => 'badge text-bg-primary'],
        Reservation::STATUS_CHECKED_IN => ['label' => 'Check-in realizado', 'badge' => 'badge text-bg-success'],
        Reservation::STATUS_CHECKED_OUT => ['label' => 'Check-out realizado', 'badge' => 'badge text-bg-secondary'],
        Reservation::STATUS_CANCELLED => ['label' => 'Cancelada', 'badge' => 'badge text-bg-danger'],
        Reservation::STATUS_EXPIRED => ['label' => 'Expirada', 'badge' => 'badge text-bg-secondary'],
        Reservation::STATUS_NO_SHOW => ['label' => 'No se presento', 'badge' => 'badge text-bg-dark'],
    ];

    private const ROOM_STATUSES = [
        'available' => ['label' => 'Disponible', 'badge' => 'badge text-bg-success'],
        'occupied' => ['label' => 'Ocupada', 'badge' => 'badge text-bg-danger'],
        'reserved' => ['label' => 'Reservada', 'badge' => 'badge text-bg-warning'],
    ];

    public function index(): View
    {
        abort_unless(auth()->user()->can('reservas.ver') || auth()->user()->can('habitaciones.ver'), 403);

        return view('adminlte.front-desk.index', [
            'roomStatuses' => self::ROOM_STATUSES,
            'operationalRoomStatuses' => [
                'available' => self::ROOM_STATUSES['available']['label'],
                'reserved' => self::ROOM_STATUSES['reserved']['label'],
                'occupied' => self::ROOM_STATUSES['occupied']['label'],
            ],
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

    public function checkIn(CheckInRequest $request, Reservation $reservation): JsonResponse
    {
        abort_unless(auth()->user()->can('reservas.checkin'), 403);

        if ($reservation->status !== Reservation::STATUS_CONFIRMED) {
            return response()->json([
                'message' => 'Solo se puede realizar check-in a reservas confirmadas.',
            ], 422);
        }

        $validated = $request->validated();

        DB::transaction(function () use ($reservation, $validated): void {
            $reservation->loadMissing('room');
            $room = $reservation->room;

            if (! $room) {
                abort(422, 'La reserva no tiene una habitacion asociada.');
            }

            $occupiedByAnotherReservation = Reservation::query()
                ->where('room_id', $room->id)
                ->where('id', '!=', $reservation->id)
                ->where('status', Reservation::STATUS_CHECKED_IN)
                ->exists();

            if ($occupiedByAnotherReservation) {
                abort(422, 'La habitacion ya esta ocupada por otra reserva activa.');
            }

            $reservation->update([
                'status' => Reservation::STATUS_CHECKED_IN,
                'checked_in_at' => now(),
                'internal_notes' => $this->appendOperationalNotes($reservation->internal_notes, 'Check-in', $validated['notes'] ?? null),
                'updated_by' => auth()->id(),
            ]);

            $room->update([
                'status' => 'occupied',
            ]);
        });

        return response()->json([
            'message' => 'Check-in realizado correctamente.',
        ]);
    }

    public function checkOut(CheckOutRequest $request, Reservation $reservation): JsonResponse
    {
        abort_unless(auth()->user()->can('reservas.checkout'), 403);

        if ($reservation->status !== Reservation::STATUS_CHECKED_IN) {
            return response()->json([
                'message' => 'Solo se puede realizar check-out a reservas con check-in activo.',
            ], 422);
        }

        $validated = $request->validated();
        $forceCheckout = filter_var($validated['force_checkout'] ?? false, FILTER_VALIDATE_BOOL);

        if ((float) $reservation->balance_amount > 0 && ! $forceCheckout) {
            return response()->json([
                'message' => 'La reserva tiene saldo pendiente. Confirme si desea realizar check-out de todas formas.',
            ], 422);
        }

        DB::transaction(function () use ($reservation, $validated): void {
            $reservation->loadMissing('room');

            $reservation->update([
                'status' => Reservation::STATUS_CHECKED_OUT,
                'checked_out_at' => now(),
                'internal_notes' => $this->appendOperationalNotes($reservation->internal_notes, 'Check-out', $validated['notes'] ?? null),
                'updated_by' => auth()->id(),
            ]);

            if ($reservation->room) {
                $reservation->room->update([
                    'status' => 'available',
                ]);
            }
        });

        return response()->json([
            'message' => 'Check-out realizado correctamente. La habitacion quedo disponible.',
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
                abort(422, 'No se puede marcar disponible una habitacion con una reserva checked_in activa.');
            }

            if ($validated['status'] !== 'occupied' && $hasCheckedInReservation) {
                abort(422, 'No se puede cambiar el estado de una habitacion con una reserva checked-in activa.');
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
}
