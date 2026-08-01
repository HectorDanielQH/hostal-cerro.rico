<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hotel_settings', function (Blueprint $table): void {
            $table->string('digital_wallet_qr_image')->nullable()->after('payment_qr_image');
            $table->string('bank_qr_image')->nullable()->after('digital_wallet_qr_image');
        });

        DB::table('hotel_settings')
            ->whereNotNull('payment_qr_image')
            ->whereNull('digital_wallet_qr_image')
            ->update([
                'digital_wallet_qr_image' => DB::raw('payment_qr_image'),
            ]);
    }

    public function down(): void
    {
        Schema::table('hotel_settings', function (Blueprint $table): void {
            $table->dropColumn(['digital_wallet_qr_image', 'bank_qr_image']);
        });
    }
};
