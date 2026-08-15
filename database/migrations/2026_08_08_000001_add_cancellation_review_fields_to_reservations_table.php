<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table): void {
            $table->timestamp('cancellation_reviewed_at')->nullable()->after('cancellation_reason');
            $table->foreignId('cancellation_reviewed_by')->nullable()->after('cancellation_reviewed_at')->constrained('users')->nullOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('cancellation_reviewed_by');
            $table->dropColumn('cancellation_reviewed_at');
        });
    }
};
