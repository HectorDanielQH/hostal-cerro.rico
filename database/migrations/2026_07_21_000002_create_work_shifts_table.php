<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_shifts', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 100);
            $table->time('starts_at');
            $table->time('ends_at');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('work_shift_id')
                ->nullable()
                ->after('is_active')
                ->constrained('work_shifts')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('work_shift_id');
        });

        Schema::dropIfExists('work_shifts');
    }
};
