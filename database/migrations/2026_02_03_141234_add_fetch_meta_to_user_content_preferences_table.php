<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('user_content_preferences', function (Blueprint $table) {
            $table->text('fetch_meta')->nullable()->after('smart_fetch');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_content_preferences', function (Blueprint $table) {
            $table->dropColumn('fetch_meta');
        });
    }
};
