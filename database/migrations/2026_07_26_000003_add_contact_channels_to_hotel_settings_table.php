<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hotel_settings', function (Blueprint $table): void {
            if (! Schema::hasColumn('hotel_settings', 'contact_people')) {
                $table->json('contact_people')->nullable()->after('whatsapp');
            }

            if (! Schema::hasColumn('hotel_settings', 'contact_emails')) {
                $table->json('contact_emails')->nullable()->after('email');
            }
        });
    }

    public function down(): void
    {
        Schema::table('hotel_settings', function (Blueprint $table): void {
            if (Schema::hasColumn('hotel_settings', 'contact_people')) {
                $table->dropColumn('contact_people');
            }

            if (Schema::hasColumn('hotel_settings', 'contact_emails')) {
                $table->dropColumn('contact_emails');
            }
        });
    }
};
