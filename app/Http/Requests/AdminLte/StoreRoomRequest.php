<?php

namespace App\Http\Requests\AdminLte;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('habitaciones.crear');
    }

    public function rules(): array
    {
        return [
            'room_type_id' => ['required', 'exists:room_types,id'],
            'number' => ['required', 'string', 'max:50', 'unique:rooms,number'],
            'floor' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'internal_notes' => ['nullable', 'string'],
            'status' => ['required', 'in:available,occupied,reserved'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
