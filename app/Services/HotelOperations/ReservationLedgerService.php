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
            return 0.0;
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
