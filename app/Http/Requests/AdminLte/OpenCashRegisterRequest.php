<?php

namespace App\Http\Requests\AdminLte;

use App\Models\CashRegister;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class OpenCashRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('caja.abrir');
    }

    public function rules(): array
    {
        return [
            'opening_amount' => ['required', 'numeric', 'min:0', 'max:999999'],
            'shift_name' => ['nullable', 'string', 'max:100'],
            'opening_notes' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $hasOpenRegister = CashRegister::query()
                ->where('user_id', auth()->id())
                ->where('status', CashRegister::STATUS_OPEN)
                ->exists();

            if ($hasOpenRegister) {
                $validator->errors()->add('opening_amount', 'Ya tienes una caja abierta y no puedes abrir otra al mismo tiempo.');
            }
        });
    }
}
