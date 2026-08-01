<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('room_types', function (Blueprint $table): void {
            $table->json('gallery_images')->nullable()->after('main_image');
        });
    }

    public function down(): void
    {
        Schema::table('room_types', function (Blueprint $table): void {
            $table->dropColumn('gallery_images');
        });
    }
};
