<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotions', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 255);
            $table->string('slug', 255)->unique();
            $table->text('description')->nullable();
            $table->string('discount_type', 50);
            $table->decimal('discount_value', 10, 2);
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->unsignedInteger('minimum_nights')->nullable();
            $table->unsignedInteger('maximum_uses')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->boolean('show_on_website')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};
