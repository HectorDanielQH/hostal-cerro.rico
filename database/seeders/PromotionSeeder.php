<?php

namespace Database\Seeders;

use App\Models\Promotion;
use App\Models\RoomType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class PromotionSeeder extends Seeder
{
    public function run(): void
    {
        $matrimonial = RoomType::query()->where('name', 'Matrimonial')->first();
        $suite = RoomType::query()->where('name', 'Suite')->first();
        $familiar = RoomType::query()->where('name', 'Familiar')->first();
        $allActiveIds = RoomType::query()->where('is_active', true)->pluck('id')->all();

        $promoInvierno = Promotion::updateOrCreate(
            ['slug' => Str::slug('Promo Invierno')],
            [
                'name' => 'Promo Invierno',
                'slug' => Str::slug('Promo Invierno'),
                'description' => '20% de descuento para habitaciones seleccionadas durante la temporada de invierno.',
                'discount_type' => 'percentage',
                'discount_value' => 20,
                'starts_at' => Carbon::now()->toDateString(),
                'ends_at' => Carbon::now()->addDays(30)->toDateString(),
                'minimum_nights' => 1,
                'maximum_uses' => null,
                'used_count' => 0,
                'show_on_website' => true,
                'is_active' => true,
            ]
        );
        $promoInvierno->roomTypes()->sync(array_values(array_filter([$matrimonial?->id, $suite?->id])));

        $promoFinDeSemana = Promotion::updateOrCreate(
            ['slug' => Str::slug('Promo Fin de Semana')],
            [
                'name' => 'Promo Fin de Semana',
                'slug' => Str::slug('Promo Fin de Semana'),
                'description' => 'Bs. 50 de descuento para estadias de fin de semana en habitaciones familiares.',
                'discount_type' => 'fixed',
                'discount_value' => 50,
                'starts_at' => null,
                'ends_at' => null,
                'minimum_nights' => 2,
                'maximum_uses' => null,
                'used_count' => 0,
                'show_on_website' => true,
                'is_active' => true,
            ]
        );
        $promoFinDeSemana->roomTypes()->sync(array_values(array_filter([$familiar?->id])));

        $promoEstadiaLarga = Promotion::updateOrCreate(
            ['slug' => Str::slug('Promo Estadia Larga')],
            [
                'name' => 'Promo Estadia Larga',
                'slug' => Str::slug('Promo Estadia Larga'),
                'description' => '10% de descuento para reservas de mas de 3 noches.',
                'discount_type' => 'percentage',
                'discount_value' => 10,
                'starts_at' => null,
                'ends_at' => null,
                'minimum_nights' => 3,
                'maximum_uses' => null,
                'used_count' => 0,
                'show_on_website' => true,
                'is_active' => true,
            ]
        );
        $promoEstadiaLarga->roomTypes()->sync($allActiveIds);
    }
}
