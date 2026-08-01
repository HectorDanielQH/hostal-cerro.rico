<?php

namespace App\Services\Mail;

use App\Models\HotelSetting;
use App\Models\Payment;
use App\Models\Reservation;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class PaymentMailService
{
    public function sendStatusEmail(Payment $payment, string $statusContext): bool
    {
        $payment->loadMissing(['reservation.room', 'reservation.roomType', 'customer']);

        if (! $this->isValidEmail($payment->customer?->email)) {
            Log::warning('No se pudo enviar correo de pago: cliente sin email valido.', [
                'payment_id' => $payment->id,
                'customer_id' => $payment->customer?->id,
            ]);

            return false;
        }

        $labels = [
            Payment::STATUS_CONFIRMED => 'Comprobante aprobado',
            Payment::STATUS_REJECTED => 'Comprobante rechazado u observado',
            Payment::STATUS_CANCELLED => 'Comprobante observado',
            Payment::STATUS_REFUNDED => 'Pago devuelto',
        ];

        $subject = ($labels[$statusContext] ?? 'Actualizacion de comprobante').' - '.$payment->code;

        return $this->sendSafely('payment_status_'.$payment->id, function () use ($payment, $statusContext, $labels, $subject): void {
            Mail::send('emails.payments.customer-status', [
                'payment' => $payment,
                'reservation' => $payment->reservation,
                'customer' => $payment->customer,
                'statusContext' => $statusContext,
                'statusLabel' => $labels[$statusContext] ?? 'Actualizacion de comprobante',
                'portalUrl' => $payment->reservation
                    ? $this->portalUrl($payment->reservation)
                    : route('public.customer-portal.search'),
            ], function ($message) use ($payment, $subject): void {
                $message
                    ->from($this->fromAddress(), $this->fromName())
                    ->to($payment->customer->email, $payment->customer->full_name)
                    ->subject($subject);
            });
        });
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
            Log::warning('No se pudo enviar correo de pago.', [
                'context' => $context,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }
}
