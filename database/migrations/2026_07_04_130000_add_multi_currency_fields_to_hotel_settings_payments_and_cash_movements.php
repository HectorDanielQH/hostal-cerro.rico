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
            $table->decimal('usd_exchange_rate', 12, 4)->default(6.9600)->after('currency');
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->string('currency', 10)->default('BOB')->after('amount');
            $table->decimal('exchange_rate', 12, 4)->default(1)->after('currency');
            $table->decimal('amount_base', 12, 2)->default(0)->after('exchange_rate');
        });

        Schema::table('cash_movements', function (Blueprint $table): void {
            $table->string('currency', 10)->default('BOB')->after('amount');
            $table->decimal('exchange_rate', 12, 4)->default(1)->after('currency');
            $table->decimal('amount_base', 12, 2)->default(0)->after('exchange_rate');
        });

        DB::table('payments')->update([
            'currency' => 'BOB',
            'exchange_rate' => 1,
            'amount_base' => DB::raw('amount'),
        ]);

        DB::table('cash_movements')->update([
            'currency' => 'BOB',
            'exchange_rate' => 1,
            'amount_base' => DB::raw('amount'),
        ]);
    }

    public function down(): void
    {
        Schema::table('cash_movements', function (Blueprint $table): void {
            $table->dropColumn(['currency', 'exchange_rate', 'amount_base']);
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->dropColumn(['currency', 'exchange_rate', 'amount_base']);
        });

        Schema::table('hotel_settings', function (Blueprint $table): void {
            $table->dropColumn('usd_exchange_rate');
        });
    }
};
