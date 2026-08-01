<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;

class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('roles.ver');
    }

    public function view(User $user, Role $role): bool
    {
        return $user->can('roles.ver');
    }

    public function create(User $user): bool
    {
        return $user->can('roles.crear');
    }

    public function update(User $user, Role $role): bool
    {
        if ($role->name === 'client') {
            return false;
        }

        return $user->can('roles.editar');
    }

    public function delete(User $user, Role $role): bool
    {
        if (! $user->can('roles.eliminar')) {
            return false;
        }

        if (in_array($role->name, ['admin', 'client'], true)) {
            return false;
        }

        $usersCount = array_key_exists('users_count', $role->getAttributes())
            ? (int) $role->getAttribute('users_count')
            : $role->users()->count();

        return $usersCount === 0;
    }
}
