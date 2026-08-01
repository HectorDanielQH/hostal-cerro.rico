<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotion_room_type', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('promotion_id')->constrained('promotions')->cascadeOnDelete();
            $table->foreignId('room_type_id')->constrained('room_types')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['promotion_id', 'room_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_room_type');
    }
};
