<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hotel_settings', function (Blueprint $table): void {
            $table->string('theme_background_color', 7)->default('#f4f0e8')->after('theme_accent_color');
            $table->string('theme_surface_color', 7)->default('#fcfaf7')->after('theme_background_color');
            $table->string('theme_text_color', 7)->default('#14293f')->after('theme_surface_color');
            $table->string('theme_muted_color', 7)->default('#667789')->after('theme_text_color');
        });
    }

    public function down(): void
    {
        Schema::table('hotel_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'theme_background_color',
                'theme_surface_color',
                'theme_text_color',
                'theme_muted_color',
            ]);
        });
    }
};
