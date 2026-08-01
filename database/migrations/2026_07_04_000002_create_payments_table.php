<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 50)->unique();
            $table->foreignId('reservation_id')->constrained('reservations')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete()->cascadeOnUpdate();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('payment_method', 50);
            $table->string('status', 50)->default('pending');
            $table->date('payment_date')->nullable();
            $table->string('reference_number', 150)->nullable();
            $table->string('receipt_image')->nullable();
            $table->text('notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->text('refund_reason')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('refunded_by')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['reservation_id', 'status']);
            $table->index(['customer_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
