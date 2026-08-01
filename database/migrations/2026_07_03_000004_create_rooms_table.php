<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('room_type_id')->constrained('room_types')->restrictOnDelete()->cascadeOnUpdate();
            $table->string('number', 50)->unique();
            $table->string('floor', 50)->nullable();
            $table->text('description')->nullable();
            $table->text('internal_notes')->nullable();
            $table->string('status', 50)->default('available');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
