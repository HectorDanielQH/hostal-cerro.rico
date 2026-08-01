<?php

namespace App\Http\Requests\AdminLte;

use Illuminate\Foundation\Http\FormRequest;

class CloseCashRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('caja.cerrar');
    }

    public function rules(): array
    {
        return [
            'counted_amount' => ['required', 'numeric', 'min:0', 'max:999999'],
            'closing_notes' => ['nullable', 'string'],
        ];
    }
}
