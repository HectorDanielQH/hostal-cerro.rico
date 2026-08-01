<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('pagos.ver') || $user->can('pagos.ver_propios');
    }

    public function view(User $user, Payment $payment): bool
    {
        if ($user->can('pagos.ver')) {
            return true;
        }

        return $user->can('pagos.ver_propios') && $payment->customer?->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('pagos.crear') || $user->can('pagos.realizar');
    }

    public function update(User $user, Payment $payment): bool
    {
        return $payment->canBeUpdated() && (
            $user->can('pagos.crear') ||
            $user->can('pagos.confirmar') ||
            $user->can('pagos.cambiar_monto')
        );
    }

    public function confirm(User $user, Payment $payment): bool
    {
        return $user->can('pagos.confirmar');
    }

    public function reject(User $user, Payment $payment): bool
    {
        return $user->can('pagos.rechazar');
    }

    public function cancel(User $user, Payment $payment): bool
    {
        return $user->can('pagos.anular');
    }

    public function refund(User $user, Payment $payment): bool
    {
        return $user->can('pagos.devolver');
    }

    public function changeAmount(User $user, Payment $payment): bool
    {
        return $user->can('pagos.cambiar_monto');
    }

    public function uploadReceipt(User $user, Payment $payment): bool
    {
        return $user->can('pagos.subir_comprobante');
    }

    public function delete(User $user, Payment $payment): bool
    {
        return false;
    }
}
