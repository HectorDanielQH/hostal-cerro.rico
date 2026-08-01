<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hotel_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('hotel_name', 255);
            $table->string('legal_name', 255)->nullable();
            $table->string('slogan', 255)->nullable();
            $table->string('description_short', 500)->nullable();
            $table->text('description_long')->nullable();
            $table->string('logo')->nullable();
            $table->string('favicon')->nullable();
            $table->string('cover_image')->nullable();
            $table->string('address', 255)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('country', 100)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('whatsapp', 50)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('website', 255)->nullable();
            $table->string('facebook', 255)->nullable();
            $table->string('instagram', 255)->nullable();
            $table->string('tiktok', 255)->nullable();
            $table->text('google_maps_url')->nullable();
            $table->time('check_in_time')->nullable();
            $table->time('check_out_time')->nullable();
            $table->string('currency', 10)->default('BOB');
            $table->string('tax_name', 255)->nullable();
            $table->string('tax_number', 100)->nullable();
            $table->string('payment_qr_image')->nullable();
            $table->string('bank_name', 255)->nullable();
            $table->string('bank_account_holder', 255)->nullable();
            $table->string('bank_account_number', 100)->nullable();
            $table->text('payment_instructions')->nullable();
            $table->unsignedInteger('reservation_expiration_minutes')->default(60);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotel_settings');
    }
};
