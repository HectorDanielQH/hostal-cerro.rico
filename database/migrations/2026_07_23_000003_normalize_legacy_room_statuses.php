<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('rooms')
            ->whereIn('status', ['cleaning', 'maintenance', 'out_of_service'])
            ->update(['status' => 'available']);
    }

    public function down(): void
    {
        // Los estados retirados del flujo operativo no se restauran automaticamente.
    }
};
