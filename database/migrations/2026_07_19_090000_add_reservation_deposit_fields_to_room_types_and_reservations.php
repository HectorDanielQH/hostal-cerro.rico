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
            $table->unsignedTinyInteger('reservation_deposit_percentage')->default(20)->after('price_usd');
        });

        Schema::table('reservations', function (Blueprint $table): void {
            $table->unsignedTinyInteger('deposit_percentage')->default(20)->after('total_amount');
            $table->decimal('deposit_amount_required', 10, 2)->default(0)->after('deposit_percentage');
        });

        DB::table('room_types')->whereNull('reservation_deposit_percentage')->update([
            'reservation_deposit_percentage' => 20,
        ]);

        DB::table('reservations')->orderBy('id')->chunkById(100, function ($reservations): void {
            foreach ($reservations as $reservation) {
                $roomTypePercentage = DB::table('room_types')
                    ->where('id', $reservation->room_type_id)
                    ->value('reservation_deposit_percentage');

                $depositPercentage = max((int) ($roomTypePercentage ?? 20), 0);
                $depositAmountRequired = round(((float) $reservation->total_amount * $depositPercentage) / 100, 2);

                DB::table('reservations')
                    ->where('id', $reservation->id)
                    ->update([
                        'deposit_percentage' => $depositPercentage,
                        'deposit_amount_required' => $depositAmountRequired,
                    ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table): void {
            $table->dropColumn(['deposit_percentage', 'deposit_amount_required']);
        });

        Schema::table('room_types', function (Blueprint $table): void {
            $table->dropColumn('reservation_deposit_percentage');
        });
    }
};
