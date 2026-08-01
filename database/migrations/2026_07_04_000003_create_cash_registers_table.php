<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_registers', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 50)->unique();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete()->cascadeOnUpdate();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->decimal('opening_amount', 10, 2)->default(0);
            $table->decimal('expected_amount', 10, 2)->default(0);
            $table->decimal('counted_amount', 10, 2)->nullable();
            $table->decimal('difference_amount', 10, 2)->default(0);
            $table->decimal('total_income', 10, 2)->default(0);
            $table->decimal('total_expense', 10, 2)->default(0);
            $table->decimal('total_adjustment', 10, 2)->default(0);
            $table->string('status', 50)->default('open');
            $table->string('shift_name', 100)->nullable();
            $table->text('opening_notes')->nullable();
            $table->text('closing_notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_registers');
    }
};
