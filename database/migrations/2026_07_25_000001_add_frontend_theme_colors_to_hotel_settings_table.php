<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hotel_settings', function (Blueprint $table): void {
            $table->string('theme_primary_color', 7)->default('#2c1458')->after('cover_image');
            $table->string('theme_secondary_color', 7)->default('#c6811e')->after('theme_primary_color');
            $table->string('theme_accent_color', 7)->default('#d66a55')->after('theme_secondary_color');
        });
    }

    public function down(): void
    {
        Schema::table('hotel_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'theme_primary_color',
                'theme_secondary_color',
                'theme_accent_color',
            ]);
        });
    }
};
