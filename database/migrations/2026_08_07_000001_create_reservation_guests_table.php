<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservation_guests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('reservation_id')->constrained('reservations')->cascadeOnDelete();
            $table->string('full_name', 255);
            $table->string('document_type', 50)->nullable();
            $table->string('document_number', 100)->nullable();
            $table->string('nationality', 100)->nullable();
            $table->string('country', 100)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('relationship', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['reservation_id', 'full_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_guests');
    }
};
