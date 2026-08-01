<?php

namespace App\Http\Requests\AdminLte;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('habitaciones.editar');
    }

    public function rules(): array
    {
        $room = $this->route('room');

        return [
            'room_type_id' => ['required', 'exists:room_types,id'],
            'number' => ['required', 'string', 'max:50', Rule::unique('rooms', 'number')->ignore($room?->id)],
            'floor' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'internal_notes' => ['nullable', 'string'],
            'status' => ['required', 'in:available,occupied,reserved'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
