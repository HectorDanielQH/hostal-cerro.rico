<?php

namespace App\Services\Notifications;

use App\Models\Reservation;
use App\Models\User;
use App\Notifications\AdminLteDemoNotification;
use App\Notifications\NewReservationNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

class ReservationNotificationService
{
    /**
     * Notify the hotel staff that should react to incoming reservations.
     */
    public function newReservation(Reservation $reservation): void
    {
        $staff = $this->reservationRecipients();

        if ($staff->isEmpty()) {
            return;
        }

        Notification::send($staff, new NewReservationNotification($reservation));
    }

    public function customerCancelledReservation(Reservation $reservation): void
    {
        $staff = $this->reservationRecipients();

        if ($staff->isEmpty()) {
            return;
        }

        $reservation->loadMissing(['customer', 'room', 'roomType']);

        $customerName = $reservation->customer?->full_name ?: 'Cliente sin nombre';
        $roomLabel = $reservation->room?->number
            ? 'Hab. '.$reservation->room->number
            : ($reservation->roomType?->name ?: 'Habitacion por revisar');
        $checkIn = $reservation->check_in?->format('d/m/Y') ?: 'fecha por definir';

        Notification::send($staff, new AdminLteDemoNotification(
            title: 'Cliente solicito anulacion',
            message: sprintf('%s anulo la reserva %s de %s para entrada %s. Revisar politica y devolucion si corresponde.', $customerName, $reservation->code, $roomLabel, $checkIn),
            icon: 'bi bi-calendar-x-fill text-danger',
            url: route('adminlte.front-desk.index'),
        ));
    }

    /**
     * @return Collection<int, User>
     */
    private function reservationRecipients(): Collection
    {
        $alwaysNotify = User::query()
            ->where('is_active', true)
            ->whereHas('roles', fn ($query) => $query->whereIn('name', ['admin', 'manager', 'general_manager']))
            ->get();

        $receptionists = User::query()
            ->where('is_active', true)
            ->whereHas('roles', fn ($query) => $query->where('name', 'receptionist'))
            ->whereHas('workShift', function ($query): void {
                $now = now()->format('H:i:s');

                $query
                    ->where('is_active', true)
                    ->where(function ($shiftQuery) use ($now): void {
                        $shiftQuery
                            ->where(function ($sameDay) use ($now): void {
                                $sameDay
                                    ->whereColumn('starts_at', '<=', 'ends_at')
                                    ->where('starts_at', '<=', $now)
                                    ->where('ends_at', '>=', $now);
                            })
                            ->orWhere(function ($overnight) use ($now): void {
                                $overnight
                                    ->whereColumn('starts_at', '>', 'ends_at')
                                    ->where(function ($timeQuery) use ($now): void {
                                        $timeQuery
                                            ->where('starts_at', '<=', $now)
                                            ->orWhere('ends_at', '>=', $now);
                                    });
                            });
                    });
            })
            ->get();

        return $alwaysNotify
            ->merge($receptionists)
            ->unique('id')
            ->values();
    }
}
