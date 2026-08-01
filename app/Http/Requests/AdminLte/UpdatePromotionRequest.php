<?php

namespace App\Http\Requests\AdminLte;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdatePromotionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('promociones.editar');
    }

    public function rules(): array
    {
        $promotion = $this->route('promotion');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('promotions', 'name')->ignore($promotion?->id)],
            'description' => ['nullable', 'string'],
            'discount_type' => ['required', 'in:percentage,fixed'],
            'discount_value' => ['required', 'numeric', 'min:0.01', 'max:999999'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'minimum_nights' => ['nullable', 'integer', 'min:1', 'max:365'],
            'maximum_uses' => ['nullable', 'integer', 'min:1', 'max:999999'],
            'room_type_ids' => ['required', 'array', 'min:1'],
            'room_type_ids.*' => ['exists:room_types,id'],
            'show_on_website' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->input('discount_type') === 'percentage' && (float) $this->input('discount_value', 0) > 100) {
                $validator->errors()->add('discount_value', 'El descuento porcentual no puede ser mayor a 100.');
            }
        });
    }
}
