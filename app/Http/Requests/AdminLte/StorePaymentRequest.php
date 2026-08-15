<?php

namespace App\Http\Requests\AdminLte;

use App\Models\CashRegister;
use App\Models\HotelSetting;
use App\Models\Reservation;
use App\Services\HotelOperations\ReservationLedgerService;
use App\Services\Reservations\ReservationExpirationService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && (
            auth()->user()->can('pagos.crear') ||
            auth()->user()->can('pagos.realizar')
        );
    }

    public function rules(): array
    {
        return [
            'reservation_id' => ['required', 'exists:reservations,id'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999'],
            'currency' => ['required', Rule::in(array_keys(HotelSetting::current()->supportedCurrencies()))],
            'payment_method' => ['required', 'in:cash,qr,bank,card,other'],
            'payment_date' => ['nullable', 'date'],
            'reference_number' => ['nullable', 'string', 'max:150'],
            'receipt_image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            app(ReservationExpirationService::class)->expirePendingReservations();

            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $reservation = Reservation::query()->with('customer')->find($this->integer('reservation_id'));

            if (! $reservation) {
                return;
            }

            if ($this->requiresOpenCashRegisterForIncome()) {
                $hasOpenCashRegister = CashRegister::query()
                    ->where('user_id', auth()->id())
                    ->where('status', CashRegister::STATUS_OPEN)
                    ->exists();

                if (! $hasOpenCashRegister) {
                    $validator->errors()->add('reservation_id', 'Debe abrir caja antes de registrar ingresos.');
                }
            }

            $hotelSetting = HotelSetting::current();
            $ledger = app(ReservationLedgerService::class);
            $currency = $hotelSetting->normalizeCurrency((string) $this->input('currency'));
            $amountBase = $ledger->paymentAmountForReservationBalance($reservation, (float) $this->input('amount', 0), $currency);
            $lockedCurrency = $ledger->lockedPaymentCurrency($reservation);

            if ($lockedCurrency && $lockedCurrency !== $currency) {
                $validator->errors()->add('currency', 'Esta reserva ya tiene pagos en '.$lockedCurrency.'. Los siguientes pagos deben registrarse en la misma moneda.');
            }

            if (! $ledger->supportsCurrency($reservation, $currency)) {
                $validator->errors()->add('currency', 'La reserva seleccionada no tiene precio configurado para '.$currency.'.');
            }

            if (in_array($reservation->status, [Reservation::STATUS_CANCELLED, Reservation::STATUS_EXPIRED], true)) {
                $validator->errors()->add('reservation_id', 'No se pueden registrar pagos para una reserva cancelada o expirada.');
            }

            if (
                $reservation->status === Reservation::STATUS_CHECKED_OUT
                && (float) $reservation->balance_amount <= 0
            ) {
                $validator->errors()->add('reservation_id', 'La reserva seleccionada ya fue pagada completamente y esta cerrada.');
            }

            if (
                $amountBase > (float) $reservation->balance_amount
                && ! auth()->user()->can('pagos.cambiar_monto')
            ) {
                $validator->errors()->add('amount', 'El monto no puede ser mayor al saldo pendiente de la reserva.');
            }

            if (
                auth()->user()->can('pagos.realizar')
                && ! auth()->user()->can('pagos.crear')
                && $reservation->customer?->user_id !== auth()->id()
            ) {
                $validator->errors()->add('reservation_id', 'Solo puedes registrar pagos para tus propias reservas.');
            }
        });
    }

    private function requiresOpenCashRegisterForIncome(): bool
    {
        $user = auth()->user();

        return $user !== null
            && $user->can('caja.abrir')
            && ! $user->can('caja.ver_todos');
    }
}
