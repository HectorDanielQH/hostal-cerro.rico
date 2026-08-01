<?php

namespace App\Policies;

use App\Models\Promotion;
use App\Models\User;

class PromotionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('promociones.ver');
    }

    public function view(User $user, Promotion $promotion): bool
    {
        return $user->can('promociones.ver');
    }

    public function create(User $user): bool
    {
        return $user->can('promociones.crear');
    }

    public function update(User $user, Promotion $promotion): bool
    {
        return $user->can('promociones.editar');
    }

    public function delete(User $user, Promotion $promotion): bool
    {
        return $user->can('promociones.eliminar');
    }
}
