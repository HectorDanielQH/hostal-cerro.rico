<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hotel_settings', function (Blueprint $table): void {
            $table->text('hero_video_url')->nullable()->after('hero_video');
        });
    }

    public function down(): void
    {
        Schema::table('hotel_settings', function (Blueprint $table): void {
            $table->dropColumn('hero_video_url');
        });
    }
};
