<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;

class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('clientes.ver');
    }

    public function view(User $user, Customer $customer): bool
    {
        return $user->can('clientes.ver');
    }

    public function create(User $user): bool
    {
        return $user->can('clientes.crear');
    }

    public function update(User $user, Customer $customer): bool
    {
        return $user->can('clientes.editar');
    }

    public function delete(User $user, Customer $customer): bool
    {
        return $user->can('clientes.eliminar');
    }
}
