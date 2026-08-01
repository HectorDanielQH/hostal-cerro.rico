<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('room_types', function (Blueprint $table): void {
            $table->decimal('price_bob', 10, 2)->default(0)->after('base_price');
            $table->decimal('price_usd', 10, 2)->default(0)->after('price_bob');
        });

        $exchangeRate = (float) DB::table('hotel_settings')->value('usd_exchange_rate');
        $exchangeRate = $exchangeRate > 0 ? $exchangeRate : 6.96;

        DB::table('room_types')->update([
            'price_bob' => DB::raw('base_price'),
            'price_usd' => DB::raw('ROUND(base_price / '.$exchangeRate.', 2)'),
        ]);
    }

    public function down(): void
    {
        Schema::table('room_types', function (Blueprint $table): void {
            $table->dropColumn(['price_bob', 'price_usd']);
        });
    }
};
