<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * LinkedIn IDs (public identifier / profile ID) can be longer than 50 chars.
     * Allow up to 255 to avoid truncation errors when saving from extension or profile.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();
        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement('ALTER TABLE users MODIFY linkedin_id VARCHAR(255) NULL');
        }
        // If using PostgreSQL: ALTER TABLE users ALTER COLUMN linkedin_id TYPE VARCHAR(255);
        // If using SQLite: column length is largely ignored; no change needed.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();
        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement('ALTER TABLE users MODIFY linkedin_id VARCHAR(50) NULL');
        }
    }
};
