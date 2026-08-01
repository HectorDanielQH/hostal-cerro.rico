<?php

namespace Database\Seeders;

use App\Models\Payment;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Database\Seeder;

class PaymentSeeder extends Seeder
{
    public function run(): void
    {
        $reservations = Reservation::query()->with('customer')->orderBy('id')->get();

        if ($reservations->isEmpty()) {
            return;
        }

        $adminUser = User::query()->role('admin')->orderBy('id')->first();

        $firstReservation = $reservations->first();
        if ($firstReservation) {
            $amount = min(100, max((float) $firstReservation->balance_amount, 0.01));

            Payment::query()->updateOrCreate(
                ['code' => 'PAY-SEED-PENDING-0001'],
                [
                    'reservation_id' => $firstReservation->id,
                    'customer_id' => $firstReservation->customer_id,
                    'amount' => $amount,
                    'currency' => 'BOB',
                    'exchange_rate' => 1,
                    'amount_base' => $amount,
                    'payment_method' => 'qr',
                    'status' => Payment::STATUS_PENDING,
                    'reference_number' => 'QR-TEST-001',
                    'payment_date' => now()->toDateString(),
                    'created_by' => $adminUser?->id,
                ]
            );
        }

        $secondReservation = $reservations->skip(1)->first() ?? $reservations->first();
        if ($secondReservation) {
            $amount = min(100, max((float) $secondReservation->balance_amount, 0.01));

            $payment = Payment::query()->updateOrCreate(
                ['code' => 'PAY-SEED-CONFIRMED-0001'],
                [
                    'reservation_id' => $secondReservation->id,
                    'customer_id' => $secondReservation->customer_id,
                    'amount' => $amount,
                    'currency' => 'BOB',
                    'exchange_rate' => 1,
                    'amount_base' => $amount,
                    'payment_method' => 'cash',
                    'status' => Payment::STATUS_CONFIRMED,
                    'payment_date' => now()->toDateString(),
                    'confirmed_at' => now(),
                    'created_by' => $adminUser?->id,
                    'confirmed_by' => $adminUser?->id,
                ]
            );

            $reservation = $payment->reservation()->first();
            if ($reservation) {
                $confirmedTotal = (float) $reservation->payments()
                    ->where('status', Payment::STATUS_CONFIRMED)
                    ->sum('amount_base');

                $reservation->update([
                    'paid_amount' => round($confirmedTotal, 2),
                    'balance_amount' => max(round((float) $reservation->total_amount - $confirmedTotal, 2), 0),
                ]);
            }
        }
    }
}
