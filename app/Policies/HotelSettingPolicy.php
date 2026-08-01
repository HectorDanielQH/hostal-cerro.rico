<?php

namespace App\Policies;

use App\Models\HotelSetting;
use App\Models\User;

class HotelSettingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('configuracion.ver');
    }

    public function view(User $user, HotelSetting $hotelSetting): bool
    {
        return $user->can('configuracion.ver');
    }

    public function update(User $user, HotelSetting $hotelSetting): bool
    {
        return $user->can('configuracion.editar');
    }
}
