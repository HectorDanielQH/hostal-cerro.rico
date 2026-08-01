<?php

namespace App\Http\Requests\AdminLte;

use App\Models\CashRegister;
use App\Models\HotelSetting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreCashMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('caja.ajustar');
    }

    public function rules(): array
    {
        return [
            'cash_register_id' => ['required', 'exists:cash_registers,id'],
            'type' => ['required', 'in:income,expense,adjustment'],
            'concept' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999'],
            'currency' => ['required', Rule::in(array_keys(HotelSetting::current()->supportedCurrencies()))],
            'payment_method' => ['nullable', 'in:cash,qr,bank,card,other'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $cashRegister = CashRegister::query()->find($this->integer('cash_register_id'));

            if (! $cashRegister) {
                return;
            }

            if ($cashRegister->status !== CashRegister::STATUS_OPEN) {
                $validator->errors()->add('cash_register_id', 'Solo se permiten movimientos manuales en cajas abiertas.');
            }

            if (! auth()->user()->can('caja.ver_todos') && $cashRegister->user_id !== auth()->id()) {
                $validator->errors()->add('cash_register_id', 'No puedes registrar movimientos en la caja de otro usuario.');
            }
        });
    }
}
