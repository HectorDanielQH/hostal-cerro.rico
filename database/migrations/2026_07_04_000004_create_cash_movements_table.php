<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_movements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cash_register_id')->constrained('cash_registers')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete()->cascadeOnUpdate();
            $table->string('type', 50);
            $table->string('concept', 255);
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('payment_method', 50)->nullable();
            $table->timestamp('movement_date')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['cash_register_id', 'type']);
            $table->index(['payment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_movements');
    }
};
