<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('full_name', 255);
            $table->string('document_type', 50)->nullable();
            $table->string('document_number', 100)->nullable();
            $table->string('nationality', 100)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('whatsapp', 50)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('address', 255)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('country', 100)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_foreign')->default(false);
            $table->boolean('is_company')->default(false);
            $table->string('company_name', 255)->nullable();
            $table->string('tax_number', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
