<?php

namespace App\Http\Requests\AdminLte;

use App\Models\HotelSetting;
use App\Models\Payment;
use App\Models\Reservation;
use App\Services\HotelOperations\ReservationLedgerService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdatePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
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
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $payment = $this->route('payment');

            if (! $payment instanceof Payment) {
                return;
            }

            $reservation = Reservation::query()->find($payment->reservation_id);

            if (! $reservation) {
                return;
            }

            $hotelSetting = HotelSetting::current();
            $ledger = app(ReservationLedgerService::class);
            $currency = $hotelSetting->normalizeCurrency((string) $this->input('currency'));
            $amountBase = $ledger->paymentAmountForReservationBalance($reservation, (float) $this->input('amount', 0), $currency);
            $lockedCurrency = $ledger->lockedPaymentCurrency($reservation, $payment);

            if ($lockedCurrency && $lockedCurrency !== $currency) {
                $validator->errors()->add('currency', 'Esta reserva ya tiene pagos en '.$lockedCurrency.'. Los siguientes pagos deben registrarse en la misma moneda.');
            }

            if (! $ledger->supportsCurrency($reservation, $currency)) {
                $validator->errors()->add('currency', 'La reserva seleccionada no tiene precio configurado para '.$currency.'.');
            }

            if (! $payment->canBeUpdated()) {
                $validator->errors()->add('amount', 'Solo se pueden editar pagos pendientes, rechazados o confirmados.');
            }

            $currentConfirmedBase = $payment->status === Payment::STATUS_CONFIRMED
                ? (float) ($payment->amount_base ?? 0)
                : 0.0;
            $availableBalance = round((float) $reservation->balance_amount + $currentConfirmedBase, 2);

            if (
                $amountBase > $availableBalance
                && ! auth()->user()->can('pagos.cambiar_monto')
            ) {
                $validator->errors()->add('amount', 'El monto no puede ser mayor al saldo pendiente de la reserva.');
            }

            if ($payment->status === Payment::STATUS_CONFIRMED) {
                $paidAfterEdit = round((float) $reservation->paid_amount - $currentConfirmedBase + $amountBase, 2);

                if (
                    in_array($reservation->status, [Reservation::STATUS_CONFIRMED, Reservation::STATUS_CHECKED_IN], true)
                    && $paidAfterEdit < round((float) $reservation->deposit_amount_required, 2)
                ) {
                    $validator->errors()->add('amount', 'No puedes dejar una reserva confirmada con un pago menor al anticipo minimo requerido.');
                }

                if (
                    $reservation->status === Reservation::STATUS_CHECKED_OUT
                    && $paidAfterEdit < round((float) $reservation->total_amount, 2)
                ) {
                    $validator->errors()->add('amount', 'No puedes dejar una reserva con salida registrada y saldo pendiente.');
                }
            }

        });
    }
}
