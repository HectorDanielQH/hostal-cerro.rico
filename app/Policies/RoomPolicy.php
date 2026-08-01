<?php

namespace App\Policies;

use App\Models\Room;
use App\Models\User;

class RoomPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('habitaciones.ver');
    }

    public function view(User $user, Room $room): bool
    {
        return $user->can('habitaciones.ver');
    }

    public function create(User $user): bool
    {
        return $user->can('habitaciones.crear');
    }

    public function update(User $user, Room $room): bool
    {
        return $user->can('habitaciones.editar');
    }

    public function delete(User $user, Room $room): bool
    {
        return $user->can('habitaciones.eliminar');
    }

    public function changeStatus(User $user, Room $room): bool
    {
        return $user->can('habitaciones.estado');
    }
}
