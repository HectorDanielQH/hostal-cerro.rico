<?php

namespace App\Http\Requests\AdminLte;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('usuarios.editar');
    }

    public function rules(): array
    {
        $user = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role' => [
                'required',
                Rule::notIn(['client']),
                Rule::exists(config('permission.table_names.roles'), 'name'),
            ],
            'work_shift_id' => [
                'nullable',
                Rule::requiredIf(fn (): bool => $this->input('role') === 'receptionist'),
                Rule::exists('work_shifts', 'id')->where('is_active', true),
            ],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'work_shift_id' => 'turno de recepcion',
        ];
    }
}
