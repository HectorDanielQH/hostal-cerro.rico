<?php

namespace App\Policies;

use App\Models\RoomType;
use App\Models\User;

class RoomTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('tipos_habitacion.ver');
    }

    public function view(User $user, RoomType $roomType): bool
    {
        return $user->can('tipos_habitacion.ver');
    }

    public function create(User $user): bool
    {
        return $user->can('tipos_habitacion.crear');
    }

    public function update(User $user, RoomType $roomType): bool
    {
        return $user->can('tipos_habitacion.editar');
    }

    public function delete(User $user, RoomType $roomType): bool
    {
        return $user->can('tipos_habitacion.eliminar');
    }
}
