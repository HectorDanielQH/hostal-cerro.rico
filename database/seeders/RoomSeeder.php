<?php

namespace Database\Seeders;

use App\Models\Room;
use App\Models\RoomType;
use Illuminate\Database\Seeder;

class RoomSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['number' => '101', 'room_type' => 'Simple', 'floor' => '1', 'status' => 'available'],
            ['number' => '102', 'room_type' => 'Doble', 'floor' => '1', 'status' => 'available'],
            ['number' => '103', 'room_type' => 'Matrimonial', 'floor' => '1', 'status' => 'available'],
            ['number' => '201', 'room_type' => 'Familiar', 'floor' => '2', 'status' => 'available'],
            ['number' => '202', 'room_type' => 'Suite', 'floor' => '2', 'status' => 'available'],
        ];

        foreach ($rows as $row) {
            $roomType = RoomType::query()->where('name', $row['room_type'])->first();

            if (! $roomType) {
                continue;
            }

            Room::updateOrCreate(
                ['number' => $row['number']],
                [
                    'room_type_id' => $roomType->id,
                    'floor' => $row['floor'],
                    'description' => null,
                    'internal_notes' => null,
                    'status' => $row['status'],
                    'is_active' => true,
                ]
            );
        }
    }
}
