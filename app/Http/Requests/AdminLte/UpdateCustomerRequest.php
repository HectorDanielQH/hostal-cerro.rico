<?php

namespace App\Http\Requests\AdminLte;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('clientes.editar');
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'document_type' => ['nullable', 'in:ci,passport,nit,other'],
            'document_number' => ['nullable', 'string', 'max:100'],
            'nationality' => ['nullable', 'string', 'max:100'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'phone' => ['nullable', 'string', 'max:50'],
            'whatsapp' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'is_foreign' => ['nullable', 'boolean'],
            'is_company' => ['nullable', 'boolean'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'tax_number' => ['nullable', 'string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
