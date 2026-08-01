<?php

namespace App\Policies;

use App\Models\CashRegister;
use App\Models\User;

class CashRegisterPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('caja.ver');
    }

    public function view(User $user, CashRegister $cashRegister): bool
    {
        if ($user->can('caja.ver_todos')) {
            return true;
        }

        return $user->can('caja.ver') && $cashRegister->user_id === $user->id;
    }

    public function open(User $user): bool
    {
        return $user->can('caja.abrir');
    }

    public function close(User $user, CashRegister $cashRegister): bool
    {
        if (! $user->can('caja.cerrar')) {
            return false;
        }

        return $cashRegister->user_id === $user->id || $user->can('caja.ver_todos');
    }

    public function arqueo(User $user, CashRegister $cashRegister): bool
    {
        if (! $user->can('caja.arqueo')) {
            return false;
        }

        return $cashRegister->user_id === $user->id || $user->can('caja.ver_todos');
    }

    public function adjust(User $user, CashRegister $cashRegister): bool
    {
        if (! $user->can('caja.ajustar')) {
            return false;
        }

        return $cashRegister->user_id === $user->id || $user->can('caja.ver_todos');
    }

    public function delete(User $user, CashRegister $cashRegister): bool
    {
        return false;
    }
}
