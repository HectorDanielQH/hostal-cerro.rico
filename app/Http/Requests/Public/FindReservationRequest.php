<?php

namespace App\Http\Requests\Public;

use Illuminate\Foundation\Http\FormRequest;

class FindReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50'],
            'contact' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'El codigo de reserva es obligatorio.',
            'contact.required' => 'Ingrese el email o WhatsApp registrado en la reserva.',
        ];
    }
}
