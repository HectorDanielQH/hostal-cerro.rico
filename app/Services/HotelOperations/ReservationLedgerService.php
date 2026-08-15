<?php

namespace App\Services\HotelOperations;

use App\Models\HotelSetting;
use App\Models\Payment;
use App\Models\Reservation;

class ReservationLedgerService
{
    public function paymentAmountForReservationBalance(Reservation $reservation, float $amount, ?string $currency): float
    {
        $currency = strtoupper(trim((string) ($currency ?: 'BOB')));
        $amount = round(max($amount, 0), 2);

        if ($currency === 'BOB') {
            return $amount;
        }

        if ($currency !== 'USD') {
            return HotelSetting::current()->convertToBase($amount, $currency);
        }

        $rate = $this->reservationUsdToBobRate($reservation);

        return $rate > 0 ? round($amount * $rate, 2) : 0.0;
    }

    public function reservationUsdToBobRate(Reservation $reservation): float
    {
        $reservation->loadMissing('roomType', 'room.roomType');

        $roomType = $reservation->roomType ?: $reservation->room?->roomType;
        $priceBob = (float) ($roomType?->priceBob() ?? 0);
        $priceUsd = (float) ($roomType?->priceUsd() ?? 0);

        if ($priceBob <= 0 || $priceUsd <= 0) {
            return 0.0;
        }

        return round($priceBob / $priceUsd, 4);
    }

    public function supportsCurrency(Reservation $reservation, ?string $currency): bool
    {
        $currency = strtoupper(trim((string) ($currency ?: 'BOB')));
        $hotelSetting = HotelSetting::current();

        if (! array_key_exists($currency, $hotelSetting->supportedCurrencies())) {
            return false;
        }

        if ($currency === $hotelSetting->baseCurrency() || $currency === 'BOB') {
            return true;
        }

        if ($currency === 'USD') {
            return $this->reservationUsdToBobRate($reservation) > 0;
        }

        return true;
    }

    public function lockedPaymentCurrency(Reservation $reservation, ?Payment $exceptPayment = null): ?string
    {
        $reservation->loadMissing('payments');

        $payment = $reservation->payments
            ->reject(fn (Payment $payment): bool => $exceptPayment && $payment->id === $exceptPayment->id)
            ->whereIn('status', [Payment::STATUS_PENDING, Payment::STATUS_CONFIRMED])
            ->sortBy('id')
            ->first();

        return $payment ? strtoupper(trim((string) ($payment->currency ?: 'BOB'))) : null;
    }

    public function ensurePaymentCurrencyMatchesReservation(Reservation $reservation, ?string $currency, ?Payment $exceptPayment = null): void
    {
        $currency = strtoupper(trim((string) ($currency ?: 'BOB')));
        $lockedCurrency = $this->lockedPaymentCurrency($reservation, $exceptPayment);

        if ($lockedCurrency && $lockedCurrency !== $currency) {
            abort(422, 'Esta reserva ya tiene pagos en '.$lockedCurrency.'. Los siguientes pagos deben registrarse en la misma moneda.');
        }
    }

    public function displayCurrency(Reservation $reservation): string
    {
        $currency = $this->lockedPaymentCurrency($reservation) ?: 'BOB';

        if ($currency === 'USD' && $this->supportsCurrency($reservation, 'USD')) {
            return 'USD';
        }

        return 'BOB';
    }

    public function amountFromBaseForDisplay(Reservation $reservation, float $amountInBase, ?string $currency = null): float
    {
        $currency = strtoupper(trim((string) ($currency ?: $this->displayCurrency($reservation))));

        if ($currency !== 'USD') {
            return round($amountInBase, 2);
        }

        $rate = $this->reservationUsdToBobRate($reservation);

        return $rate > 0 ? round($amountInBase / $rate, 2) : 0.0;
    }

    public function syncReservationAmounts(Reservation $reservation): void
    {
        $confirmedTotal = $this->confirmedAmount($reservation);

        $reservation->update([
            'paid_amount' => round($confirmedTotal, 2),
            'balance_amount' => max(round((float) $reservation->total_amount - $confirmedTotal, 2), 0),
        ]);
    }

    public function confirmedAmount(Reservation $reservation): float
    {
        return (float) $reservation->payments()
            ->where('status', Payment::STATUS_CONFIRMED)
            ->sum('amount_base');
    }
}
