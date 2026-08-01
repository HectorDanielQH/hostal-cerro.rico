<?php

namespace Database\Seeders;

use App\Models\RoomType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RoomTypeSeeder extends Seeder
{
    public function run(): void
    {
        $roomTypes = [
            [
                'name' => 'Simple',
                'base_price' => 120,
                'price_usd' => 17.24,
                'capacity_adults' => 1,
                'capacity_children' => 0,
                'max_guests' => 1,
                'amenities' => ['WiFi', 'Bano privado', 'Agua caliente'],
            ],
            [
                'name' => 'Doble',
                'base_price' => 180,
                'price_usd' => 25.86,
                'capacity_adults' => 2,
                'capacity_children' => 0,
                'max_guests' => 2,
                'amenities' => ['WiFi', 'Bano privado', 'Agua caliente'],
            ],
            [
                'name' => 'Matrimonial',
                'base_price' => 200,
                'price_usd' => 28.74,
                'capacity_adults' => 2,
                'capacity_children' => 1,
                'max_guests' => 3,
                'amenities' => ['WiFi', 'Bano privado', 'Agua caliente', 'TV'],
            ],
            [
                'name' => 'Familiar',
                'base_price' => 280,
                'price_usd' => 40.23,
                'capacity_adults' => 3,
                'capacity_children' => 2,
                'max_guests' => 5,
                'amenities' => ['WiFi', 'Bano privado', 'Agua caliente', 'TV'],
            ],
            [
                'name' => 'Suite',
                'base_price' => 350,
                'price_usd' => 50.29,
                'capacity_adults' => 2,
                'capacity_children' => 2,
                'max_guests' => 4,
                'amenities' => ['WiFi', 'Bano privado', 'Agua caliente', 'TV', 'Vista panoramica'],
            ],
        ];

        foreach ($roomTypes as $roomType) {
            RoomType::updateOrCreate(
                ['slug' => Str::slug($roomType['name'])],
                [
                    'name' => $roomType['name'],
                    'slug' => Str::slug($roomType['name']),
                    'description' => null,
                    'base_price' => $roomType['base_price'],
                    'price_bob' => $roomType['base_price'],
                    'price_usd' => $roomType['price_usd'],
                    'capacity_adults' => $roomType['capacity_adults'],
                    'capacity_children' => $roomType['capacity_children'],
                    'max_guests' => $roomType['max_guests'],
                    'main_image' => null,
                    'amenities' => $roomType['amenities'],
                    'show_on_website' => true,
                    'is_active' => true,
                ]
            );
        }
    }
}
