<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('room_types', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 255);
            $table->string('slug', 255)->unique();
            $table->text('description')->nullable();
            $table->decimal('base_price', 10, 2)->default(0);
            $table->unsignedTinyInteger('capacity_adults')->default(1);
            $table->unsignedTinyInteger('capacity_children')->default(0);
            $table->unsignedTinyInteger('max_guests')->default(1);
            $table->string('main_image')->nullable();
            $table->json('amenities')->nullable();
            $table->boolean('show_on_website')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_types');
    }
};
