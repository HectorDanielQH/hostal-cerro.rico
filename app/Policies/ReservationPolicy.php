<?php

namespace App\Policies;

use App\Models\Reservation;
use App\Models\User;

class ReservationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('reservas.ver') || $user->can('reservas.ver_propias');
    }

    public function view(User $user, Reservation $reservation): bool
    {
        if ($user->can('reservas.ver')) {
            return true;
        }

        return $user->can('reservas.ver_propias') && $reservation->customer?->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('reservas.crear');
    }

    public function update(User $user, Reservation $reservation): bool
    {
        return $user->can('reservas.editar');
    }

    public function delete(User $user, Reservation $reservation): bool
    {
        return false;
    }

    public function cancel(User $user, Reservation $reservation): bool
    {
        return $user->can('reservas.cancelar');
    }

    public function confirm(User $user, Reservation $reservation): bool
    {
        return $user->can('reservas.confirmar');
    }

    public function checkIn(User $user, Reservation $reservation): bool
    {
        return $user->can('reservas.checkin');
    }

    public function checkOut(User $user, Reservation $reservation): bool
    {
        return $user->can('reservas.checkout');
    }

    public function applyDiscount(User $user, Reservation $reservation): bool
    {
        return $user->can('reservas.aplicar_descuento');
    }

    public function changePrice(User $user, Reservation $reservation): bool
    {
        return $user->can('reservas.cambiar_precio');
    }
}
