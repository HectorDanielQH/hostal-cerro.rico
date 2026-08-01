<?php

namespace Database\Seeders;

use App\Models\HotelSetting;
use Illuminate\Database\Seeder;

class HotelSettingSeeder extends Seeder
{
    public function run(): void
    {
        HotelSetting::query()->firstOrCreate(
            ['id' => 1],
            [
                'hotel_name' => 'Hostal Cerro Rico',
                'legal_name' => 'Hostal Cerro Rico',
                'slogan' => 'Hospedaje en el corazon de Potosi',
                'city' => 'Potosi',
                'country' => 'Bolivia',
                'currency' => 'BOB',
                'enabled_currencies' => HotelSetting::defaultCurrencies(),
                'theme_primary_color' => '#2c1458',
                'theme_secondary_color' => '#c6811e',
                'theme_accent_color' => '#d66a55',
                'theme_background_color' => '#f4f0e8',
                'theme_surface_color' => '#fcfaf7',
                'theme_text_color' => '#14293f',
                'theme_muted_color' => '#667789',
                'is_active' => true,
            ]
        );
    }
}
