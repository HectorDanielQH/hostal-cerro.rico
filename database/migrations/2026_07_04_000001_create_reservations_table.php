<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 50)->unique();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('room_id')->constrained('rooms')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('room_type_id')->constrained('room_types')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('promotion_id')->nullable()->constrained('promotions')->nullOnDelete()->cascadeOnUpdate();
            $table->date('check_in');
            $table->date('check_out');
            $table->unsignedInteger('nights')->default(1);
            $table->unsignedTinyInteger('adults')->default(1);
            $table->unsignedTinyInteger('children')->default(0);
            $table->decimal('base_price', 10, 2)->default(0);
            $table->string('discount_type', 50)->nullable();
            $table->decimal('discount_value', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('price_per_night', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->decimal('balance_amount', 10, 2)->default(0);
            $table->string('status', 50)->default('pending');
            $table->string('source', 50)->default('reception');
            $table->text('special_requests')->nullable();
            $table->text('internal_notes')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('checked_out_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['room_id', 'status']);
            $table->index(['check_in', 'check_out']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
