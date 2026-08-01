<?php

namespace Database\Seeders;

use App\Models\CashRegister;
use App\Models\User;
use Illuminate\Database\Seeder;

class CashRegisterSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->role('admin')->orderBy('id')->first() ?? User::query()->orderBy('id')->first();

        if (! $user) {
            return;
        }

        $hasOpenRegister = CashRegister::query()
            ->where('user_id', $user->id)
            ->where('status', CashRegister::STATUS_OPEN)
            ->exists();

        if ($hasOpenRegister) {
            return;
        }

        CashRegister::query()->firstOrCreate(
            ['code' => 'CASH-'.now()->subDay()->format('Ymd').'-0001'],
            [
                'user_id' => $user->id,
                'opened_at' => now()->subDay()->setTime(8, 0),
                'closed_at' => now()->subDay()->setTime(16, 0),
                'opening_amount' => 100,
                'expected_amount' => 550,
                'counted_amount' => 550,
                'difference_amount' => 0,
                'total_income' => 500,
                'total_expense' => 50,
                'total_adjustment' => 0,
                'status' => CashRegister::STATUS_CLOSED,
                'shift_name' => 'Turno manana',
                'created_by' => $user->id,
                'closed_by' => $user->id,
            ]
        );
    }
}
