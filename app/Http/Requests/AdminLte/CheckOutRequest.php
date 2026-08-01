<?php

namespace App\Http\Requests\AdminLte;

use Illuminate\Foundation\Http\FormRequest;

class CheckOutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('reservas.checkout');
    }

    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string', 'max:1000'],
            'force_checkout' => ['nullable', 'boolean'],
        ];
    }
}
