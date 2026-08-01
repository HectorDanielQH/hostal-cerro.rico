<?php

namespace App\Http\Requests\AdminLte;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRoomOperationalStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('habitaciones.estado');
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:available,occupied,reserved'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
