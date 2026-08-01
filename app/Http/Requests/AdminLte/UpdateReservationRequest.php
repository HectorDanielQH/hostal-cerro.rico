<?php

namespace App\Http\Requests\AdminLte;

use App\Models\Promotion;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\HotelSetting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('reservas.editar');
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'exists:customers,id'],
            'room_id' => ['required', 'exists:rooms,id'],
            'check_in' => ['required', 'date', 'after_or_equal:today'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'adults' => ['required', 'integer', 'min:1', 'max:20'],
            'children' => ['nullable', 'integer', 'min:0', 'max:20'],
            'promotion_id' => ['nullable', 'exists:promotions,id'],
            'discount_type' => ['nullable', 'in:percentage,fixed'],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'base_price' => ['nullable', 'numeric', 'min:0'],
            'base_price_currency' => ['nullable', 'in:BOB,USD'],
            'status' => ['nullable', 'in:pending,confirmed'],
            'source' => ['nullable', 'in:reception,website,phone,whatsapp,agency,other'],
            'preferred_payment_method' => ['nullable', 'in:cash,qr,bank,bank_transfer,bank_deposit,card,other'],
            'initial_payment_amount' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'initial_payment_currency' => ['nullable', Rule::in(array_keys(HotelSetting::current()->supportedCurrencies()))],
            'initial_payment_method' => ['nullable', 'in:cash,qr,bank,card,other'],
            'initial_payment_reference' => ['nullable', 'string', 'max:150'],
            'initial_payment_notes' => ['nullable', 'string'],
            'special_requests' => ['nullable', 'string'],
            'internal_notes' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $reservation = $this->route('reservation');
            if (! $reservation instanceof Reservation) {
                return;
            }

            if (
                in_array($reservation->status, [Reservation::STATUS_CHECKED_OUT, Reservation::STATUS_CANCELLED, Reservation::STATUS_EXPIRED], true)
                && ! auth()->user()->hasRole('admin')
            ) {
                $validator->errors()->add('status', 'No se puede editar una reserva finalizada, cancelada o expirada, salvo por un administrador.');
            }

            $room = Room::query()->with('roomType')->find($this->integer('room_id'));

            if (! $room || ! $room->roomType) {
                return;
            }

            if (! $room->is_active) {
                $validator->errors()->add('room_id', 'La habitacion seleccionada no esta activa.');
            }

            $adults = (int) $this->input('adults', 0);
            $children = (int) $this->input('children', 0);
            $maxGuests = (int) ($room->roomType->max_guests ?? 0);

            if (($adults + $children) > $maxGuests) {
                $validator->errors()->add('adults', 'La cantidad total de huespedes supera la capacidad maxima del tipo de habitacion.');
            }

            $checkIn = Carbon::parse($this->input('check_in'));
            $checkOut = Carbon::parse($this->input('check_out'));

            $crossingReservationExists = Reservation::query()
                ->where('room_id', $room->id)
                ->whereIn('status', Reservation::ACTIVE_STATUSES)
                ->where('id', '!=', $reservation->id)
                ->where('check_in', '<', $checkOut->toDateString())
                ->where('check_out', '>', $checkIn->toDateString())
                ->exists();

            if ($crossingReservationExists) {
                $validator->errors()->add('room_id', 'La habitacion ya tiene otra reserva activa que se cruza con el rango de fechas seleccionado.');
            }

            $promotionId = $this->input('promotion_id');
            if ($promotionId) {
                $promotion = Promotion::query()->with('roomTypes')->find($promotionId);

                if (! $promotion) {
                    return;
                }

                if (! $promotion->roomTypes->contains('id', $room->room_type_id)) {
                    $validator->errors()->add('promotion_id', 'La promocion seleccionada no corresponde al tipo de habitacion elegido.');
                }

                if (! $promotion->isCurrentlyActive()) {
                    $validator->errors()->add('promotion_id', 'La promocion seleccionada no esta vigente.');
                }

                $nights = max($checkIn->diffInDays($checkOut), 1);
                if ($promotion->minimum_nights !== null && $nights < (int) $promotion->minimum_nights) {
                    $validator->errors()->add('promotion_id', 'La promocion requiere una cantidad minima de noches mayor a la seleccionada.');
                }
            }

            if ($this->input('discount_type') === 'percentage' && (float) $this->input('discount_value', 0) > 100) {
                $validator->errors()->add('discount_value', 'El descuento porcentual no puede superar el 100%.');
            }

            if (! auth()->user()->can('reservas.cambiar_precio') && $this->filled('base_price')) {
                $validator->errors()->add('base_price', 'No tienes permiso para sobrescribir manualmente el precio base.');
            }

            if (
                ! auth()->user()->can('reservas.aplicar_descuento')
                && ($this->filled('discount_type') || $this->filled('discount_value'))
            ) {
                $validator->errors()->add('discount_type', 'No tienes permiso para aplicar descuentos manuales.');
            }

            if ((float) $this->input('initial_payment_amount', 0) > 0) {
                if (! $this->filled('initial_payment_currency')) {
                    $validator->errors()->add('initial_payment_currency', 'Selecciona la moneda del pago recibido.');
                }

                if (! $this->filled('initial_payment_method')) {
                    $validator->errors()->add('initial_payment_method', 'Selecciona el metodo del pago recibido.');
                }
            }
        });
    }
}
