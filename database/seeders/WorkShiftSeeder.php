<?php

namespace Database\Seeders;

use App\Models\WorkShift;
use Illuminate\Database\Seeder;

class WorkShiftSeeder extends Seeder
{
    public function run(): void
    {
        $shifts = [
            [
                'name' => 'Manana',
                'starts_at' => '07:00',
                'ends_at' => '15:00',
                'description' => 'Turno operativo de apertura y atencion de reservas diurnas.',
            ],
            [
                'name' => 'Tarde',
                'starts_at' => '15:00',
                'ends_at' => '23:00',
                'description' => 'Turno operativo de recepcion, check-in y seguimiento de pagos.',
            ],
            [
                'name' => 'Noche',
                'starts_at' => '23:00',
                'ends_at' => '07:00',
                'description' => 'Turno operativo nocturno para recepcion y control interno.',
            ],
        ];

        foreach ($shifts as $shift) {
            WorkShift::query()->updateOrCreate(
                ['name' => $shift['name']],
                $shift + ['is_active' => true],
            );
        }
    }
}
