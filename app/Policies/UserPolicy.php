<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('usuarios.ver');
    }

    public function view(User $user, User $model): bool
    {
        return $user->can('usuarios.ver');
    }

    public function create(User $user): bool
    {
        return $user->can('usuarios.crear');
    }

    public function update(User $user, User $model): bool
    {
        return $user->can('usuarios.editar');
    }

    public function delete(User $user, User $model): bool
    {
        if (! $user->can('usuarios.eliminar')) {
            return false;
        }

        return $user->id !== $model->id;
    }
}
