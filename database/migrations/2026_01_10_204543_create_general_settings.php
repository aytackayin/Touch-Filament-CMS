<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // We will seed these in the DatabaseSeeder for better control, 
        // but we can ensure the table is ready here if needed.
        // Actually, Spatie's settings table migration handles the schema.
    }

    public function down(): void
    {
        //
    }
};
