<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('v2_organizations', function (Blueprint $table) {
            $table->id();
            $table->string('name', 191);
            $table->string('slug', 191)->unique();
            $table->string('status', 50)->default('active');
            $table->timestamps();
        });

        Schema::create('v2_organization_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('v2_organizations')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 50)->default('member');
            $table->json('capabilities')->nullable();
            $table->string('status', 50)->default('active');
            $table->timestamps();

            $table->unique(['organization_id', 'user_id'], 'v2_org_user_unique');
        });

        Schema::create('v2_extension_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120)->default('extension');
            $table->string('token_hash', 255)->unique();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'revoked_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'current_organization_id')) {
                $table->unsignedBigInteger('current_organization_id')->nullable()->after('remember_token');
                $table->index(['current_organization_id']);
            }
            if (! Schema::hasColumn('users', 'entitlements')) {
                $table->json('entitlements')->nullable()->after('remember_token');
            }
            if (! Schema::hasColumn('users', 'is_platform_admin')) {
                $table->boolean('is_platform_admin')->default(false)->after('entitlements');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'current_organization_id')) {
                $table->dropIndex(['current_organization_id']);
                $table->dropColumn('current_organization_id');
            }
            if (Schema::hasColumn('users', 'entitlements')) {
                $table->dropColumn('entitlements');
            }
            if (Schema::hasColumn('users', 'is_platform_admin')) {
                $table->dropColumn('is_platform_admin');
            }
        });

        Schema::dropIfExists('v2_extension_tokens');
        Schema::dropIfExists('v2_organization_user');
        Schema::dropIfExists('v2_organizations');
    }
};
