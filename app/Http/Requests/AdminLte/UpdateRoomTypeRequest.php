<?php

namespace App\Http\Requests\AdminLte;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateRoomTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('tipos_habitacion.editar');
    }

    public function rules(): array
    {
        $roomType = $this->route('roomType');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('room_types', 'name')->ignore($roomType?->id),
            ],
            'description' => ['nullable', 'string'],
            'price_bob' => ['required', 'numeric', 'min:0', 'max:999999'],
            'price_usd' => ['required', 'numeric', 'min:0', 'max:999999'],
            'reservation_deposit_percentage' => ['required', 'integer', 'min:10', 'max:100', 'multiple_of:10'],
            'capacity_adults' => ['required', 'integer', 'min:1', 'max:20'],
            'capacity_children' => ['nullable', 'integer', 'min:0', 'max:20'],
            'max_guests' => ['required', 'integer', 'min:1', 'max:40'],
            'gallery_images' => ['nullable', 'array', 'min:1', 'max:4'],
            'gallery_images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'amenities' => ['nullable', 'string'],
            'show_on_website' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $adults = (int) $this->input('capacity_adults', 0);
            $children = (int) $this->input('capacity_children', 0);
            $maxGuests = (int) $this->input('max_guests', 0);

            if ($maxGuests < ($adults + $children)) {
                $validator->errors()->add('max_guests', 'La capacidad maxima no puede ser menor a la suma de adultos y ninos.');
            }
        });
    }
}
