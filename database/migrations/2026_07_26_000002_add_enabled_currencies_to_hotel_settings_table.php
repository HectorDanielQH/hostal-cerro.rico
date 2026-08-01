<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hotel_settings', function (Blueprint $table): void {
            if (! Schema::hasColumn('hotel_settings', 'enabled_currencies')) {
                $table->json('enabled_currencies')->nullable()->after('currency');
            }
        });
    }

    public function down(): void
    {
        Schema::table('hotel_settings', function (Blueprint $table): void {
            if (Schema::hasColumn('hotel_settings', 'enabled_currencies')) {
                $table->dropColumn('enabled_currencies');
            }
        });
    }
};
