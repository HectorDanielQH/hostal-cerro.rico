<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hotel_settings', function (Blueprint $table): void {
            $table->string('mobile_hero_image')->nullable()->after('hero_video_url');
        });
    }

    public function down(): void
    {
        Schema::table('hotel_settings', function (Blueprint $table): void {
            $table->dropColumn('mobile_hero_image');
        });
    }
};
