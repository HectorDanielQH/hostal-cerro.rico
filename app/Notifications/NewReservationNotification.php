<?php

namespace App\Notifications;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewReservationNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Reservation $reservation,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $this->reservation->loadMissing(['customer', 'room', 'roomType']);

        $customerName = $this->reservation->customer?->full_name ?: 'Cliente sin nombre';
        $roomLabel = $this->reservation->room?->number
            ? 'Hab. '.$this->reservation->room->number
            : ($this->reservation->roomType?->name ?: 'Habitacion por revisar');
        $checkIn = $this->reservation->check_in?->format('d/m/Y') ?: 'fecha por definir';

        return [
            'title' => 'Tienes nueva reserva',
            'message' => sprintf('%s reservo %s para el %s.', $customerName, $roomLabel, $checkIn),
            'icon' => 'bi bi-calendar2-plus-fill text-success',
            'url' => route('adminlte.reservations.index'),
            'reservation_id' => $this->reservation->id,
            'reservation_code' => $this->reservation->code,
        ];
    }
}
