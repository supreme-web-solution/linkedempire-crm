<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('v2_integration_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 50);
            $table->string('provider_account_id', 191);
            $table->string('provider_identity_id', 191)->nullable();
            $table->string('status', 50)->default('active');
            $table->json('meta')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'provider', 'provider_account_id'], 'v2_integration_accounts_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('v2_integration_accounts');
    }
};
