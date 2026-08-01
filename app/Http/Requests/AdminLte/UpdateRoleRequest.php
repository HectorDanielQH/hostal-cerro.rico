<?php

namespace App\Http\Requests\AdminLte;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('roles.editar');
    }

    public function rules(): array
    {
        $role = $this->route('role');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::notIn(['client']),
                Rule::unique(config('permission.table_names.roles'), 'name')->ignore($role?->id),
            ],
            'label' => ['nullable', 'string', 'max:255'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => [
                'string',
                Rule::exists(config('permission.table_names.permissions'), 'name'),
            ],
        ];
    }
}
