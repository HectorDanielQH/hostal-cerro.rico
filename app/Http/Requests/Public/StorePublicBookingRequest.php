<?php

namespace App\Http\Requests\Public;

use App\Models\HotelSetting;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Services\Reservations\ReservationExpirationService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePublicBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'room_type_id' => ['required', 'exists:room_types,id'],
            'check_in' => ['required', 'date', 'after_or_equal:today'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'adults' => ['required', 'integer', 'min:1', 'max:20'],
            'children' => ['nullable', 'integer', 'min:0', 'max:20'],
            'full_name' => ['required', 'string', 'max:255'],
            'document_type' => ['nullable', Rule::in(['ci', 'passport', 'nit', 'other'])],
            'document_number' => ['nullable', 'string', 'max:100'],
            'nationality' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:50'],
            'whatsapp' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'country' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'preferred_payment_method' => ['required', Rule::in(['qr', 'bank_transfer', 'bank_deposit', 'bank', 'other'])],
            'payment_currency' => ['required', Rule::in(['BOB', 'USD'])],
            'payment_amount' => ['required', 'numeric', 'min:0.01'],
            'payment_reference_number' => ['nullable', 'string', 'max:150'],
            'receipt_image' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
            'special_requests' => ['nullable', 'string', 'max:2000'],
            'accept_terms' => ['required', 'accepted'],
        ];
    }

    public function attributes(): array
    {
        return [
            'room_type_id' => 'tipo de habitacion',
            'check_in' => 'fecha de entrada',
            'check_out' => 'fecha de salida',
            'full_name' => 'nombre completo',
            'document_type' => 'tipo de documento',
            'document_number' => 'numero de documento',
            'preferred_payment_method' => 'forma de pago preferida',
            'payment_currency' => 'moneda del deposito',
            'payment_amount' => 'monto depositado',
            'payment_reference_number' => 'numero de referencia',
            'receipt_image' => 'comprobante de pago',
            'special_requests' => 'solicitudes especiales',
            'accept_terms' => 'aceptacion de condiciones',
        ];
    }

    public function messages(): array
    {
        return [
            'accept_terms.accepted' => 'Debes aceptar que la reserva quedara pendiente de confirmacion.',
            'payment_amount.required' => 'Indica cuanto estas depositando.',
            'payment_amount.min' => 'El monto depositado debe ser mayor a 0.',
            'receipt_image.max' => 'El comprobante no debe exceder los 10 MB.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'children' => $this->input('children') === '' ? 0 : $this->input('children'),
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            app(ReservationExpirationService::class)->expirePendingReservations();

            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $roomType = RoomType::query()->find($this->integer('room_type_id'));

            if (! $roomType) {
                return;
            }

            if (! $roomType->is_active || ! $roomType->show_on_website) {
                $validator->errors()->add('room_type_id', 'El tipo de habitacion seleccionado no esta disponible para reservas online.');
            }

            $adults = (int) $this->input('adults', 0);
            $children = (int) $this->input('children', 0);
            $guests = $adults + $children;

            if ($guests > (int) $roomType->max_guests) {
                $validator->errors()->add('adults', 'La cantidad total de huespedes supera la capacidad maxima del tipo de habitacion.');
            }

            $checkIn = Carbon::parse($this->input('check_in'));
            $checkOut = Carbon::parse($this->input('check_out'));

            if ($checkOut->lessThanOrEqualTo($checkIn)) {
                $validator->errors()->add('check_out', 'La fecha de salida debe ser posterior a la fecha de entrada.');
            }

            $hotelSetting = HotelSetting::current();
            $paymentMethod = (string) $this->input('preferred_payment_method');

            if (
                $paymentMethod === 'qr'
                && blank($hotelSetting->digital_wallet_qr_image)
                && blank($hotelSetting->bank_qr_image)
                && blank($hotelSetting->payment_qr_image)
            ) {
                $validator->errors()->add('preferred_payment_method', 'El QR de pago aun no esta configurado por el hotel.');
            }

            if (
                in_array($paymentMethod, ['bank', 'bank_transfer', 'bank_deposit'], true)
                && blank($hotelSetting->bank_name)
                && blank($hotelSetting->bank_account_number)
                && blank($hotelSetting->bank_account_holder)
            ) {
                $validator->errors()->add('preferred_payment_method', 'Los datos bancarios aun no estan configurados por el hotel.');
            }

            $hasAvailability = Room::query()
                ->where('room_type_id', $roomType->id)
                ->where('is_active', true)
                ->whereDoesntHave('reservations', function ($query) use ($checkIn, $checkOut): void {
                    $query->whereIn('status', Reservation::ACTIVE_STATUSES)
                        ->where('check_in', '<', $checkOut->toDateString())
                        ->where('check_out', '>', $checkIn->toDateString());
                })
                ->exists();

            if (! $hasAvailability) {
                $validator->errors()->add('room_type_id', 'Ya no hay habitaciones disponibles de este tipo para las fechas seleccionadas.');
            }
        });
    }
}
