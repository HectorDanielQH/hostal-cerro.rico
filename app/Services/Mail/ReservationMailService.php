<?php

namespace App\Services\Mail;

use App\Models\HotelSetting;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class ReservationMailService
{
    public function sendCreatedEmails(Reservation $reservation): array
    {
        $reservation->loadMissing(['customer', 'room', 'roomType', 'promotion']);
        $result = [
            'customer' => false,
            'staff_sent' => 0,
            'staff_failed' => 0,
        ];

        if ($this->isValidEmail($reservation->customer?->email)) {
            $result['customer'] = $this->sendSafely('reservation_created_customer_'.$reservation->id, function () use ($reservation): void {
                Mail::send('emails.reservations.customer-created', [
                    'reservation' => $reservation,
                    'customer' => $reservation->customer,
                    'portalUrl' => $this->portalUrl($reservation),
                ], function ($message) use ($reservation): void {
                    $message
                        ->from($this->fromAddress(), $this->fromName())
                        ->to($reservation->customer->email, $reservation->customer->full_name)
                        ->subject('Recibimos tu solicitud de reserva '.$reservation->code);
                });
            });
        } else {
            Log::warning('No se pudo enviar correo de reserva creada: cliente sin email valido.', [
                'reservation_id' => $reservation->id,
                'customer_id' => $reservation->customer?->id,
            ]);
        }

        $this->staffRecipients()
            ->filter(fn (User $user): bool => $this->isValidEmail($user->email))
            ->each(function (User $user) use ($reservation, &$result): void {
                $sent = $this->sendSafely('reservation_created_staff_'.$reservation->id.'_user_'.$user->id, function () use ($reservation, $user): void {
                    Mail::send('emails.reservations.staff-created', [
                        'reservation' => $reservation,
                        'customer' => $reservation->customer,
                        'adminUrl' => route('adminlte.reservations.index'),
                        'recipient' => $user,
                    ], function ($message) use ($reservation, $user): void {
                        $message
                            ->from($this->fromAddress(), $this->fromName())
                            ->to($user->email, $user->name)
                            ->subject('Tienes nueva reserva: '.$reservation->code);
                    });
                });

                $sent ? $result['staff_sent']++ : $result['staff_failed']++;
            });

        return $result;
    }

    public function sendConfirmedEmail(Reservation $reservation): bool
    {
        $reservation->loadMissing(['customer', 'room', 'roomType', 'promotion']);

        if (! $this->isValidEmail($reservation->customer?->email)) {
            Log::warning('No se pudo enviar correo de reserva confirmada: cliente sin email valido.', [
                'reservation_id' => $reservation->id,
                'customer_id' => $reservation->customer?->id,
            ]);

            return false;
        }

        return $this->sendSafely('reservation_confirmed_'.$reservation->id, function () use ($reservation): void {
            Mail::send('emails.reservations.customer-confirmed', [
                'reservation' => $reservation,
                'customer' => $reservation->customer,
                'portalUrl' => $this->portalUrl($reservation),
            ], function ($message) use ($reservation): void {
                $message
                    ->from($this->fromAddress(), $this->fromName())
                    ->to($reservation->customer->email, $reservation->customer->full_name)
                    ->subject('Tu reserva fue confirmada '.$reservation->code);
            });
        });
    }

    /**
     * @return Collection<int, User>
     */
    private function staffRecipients(): Collection
    {
        $alwaysNotify = User::query()
            ->where('is_active', true)
            ->whereHas('roles', fn ($query) => $query->whereIn('name', ['admin', 'manager', 'general_manager']))
            ->get();

        $receptionists = User::query()
            ->where('is_active', true)
            ->whereHas('roles', fn ($query) => $query->where('name', 'receptionist'))
            ->whereHas('workShift', function ($query): void {
                $now = now()->format('H:i:s');

                $query
                    ->where('is_active', true)
                    ->where(function ($shiftQuery) use ($now): void {
                        $shiftQuery
                            ->where(function ($sameDay) use ($now): void {
                                $sameDay
                                    ->whereColumn('starts_at', '<=', 'ends_at')
                                    ->where('starts_at', '<=', $now)
                                    ->where('ends_at', '>=', $now);
                            })
                            ->orWhere(function ($overnight) use ($now): void {
                                $overnight
                                    ->whereColumn('starts_at', '>', 'ends_at')
                                    ->where(function ($timeQuery) use ($now): void {
                                        $timeQuery
                                            ->where('starts_at', '<=', $now)
                                            ->orWhere('ends_at', '>=', $now);
                                    });
                            });
                    });
            })
            ->get();

        return $alwaysNotify
            ->merge($receptionists)
            ->unique('id')
            ->values();
    }

    private function isValidEmail(?string $email): bool
    {
        return is_string($email) && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    private function fromAddress(): string
    {
        return (string) config('mail.from.address', 'hello@example.com');
    }

    private function fromName(): string
    {
        $hotelSetting = HotelSetting::current();

        return filled($hotelSetting->hotel_name)
            ? (string) $hotelSetting->hotel_name
            : (string) config('mail.from.name', 'Hostal Cerro Rico');
    }

    private function portalUrl(Reservation $reservation): string
    {
        return URL::temporarySignedRoute(
            'public.customer-portal.show',
            now()->addDays(180),
            ['reservation' => $reservation->code]
        );
    }

    private function sendSafely(string $context, callable $callback): bool
    {
        try {
            $callback();

            return true;
        } catch (\Throwable $exception) {
            Log::warning('No se pudo enviar correo de reserva.', [
                'context' => $context,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }
}
