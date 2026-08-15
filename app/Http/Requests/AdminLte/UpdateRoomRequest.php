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
            'gallery_images' => ['nullable', 'array', 'max:8'],
            'gallery_images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'clear_gallery_images' => ['nullable', 'boolean'],
            'status' => ['required', 'in:available,occupied,reserved,cleaning,maintenance'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
