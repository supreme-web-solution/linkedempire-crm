<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('remember_token');
                $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('users', 'entitlements')) {
                $table->json('entitlements')->nullable()->after('remember_token');
            }
            if (! Schema::hasColumn('users', 'is_platform_admin')) {
                $table->boolean('is_platform_admin')->default(false)->after('remember_token');
            }
        });

        if (! Schema::hasTable('v2_products')) {
            Schema::create('v2_products', function (Blueprint $table) {
                $table->id();
                $table->string('product_id', 32)->unique();
                $table->string('name');
                $table->json('entitlements');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('v2_product_transactions')) {
            Schema::create('v2_product_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('product_id', 32);
                $table->string('transaction_id', 64);
                $table->string('transaction_type', 16);
                $table->json('payload')->nullable();
                $table->timestamps();

                $table->unique(['transaction_id', 'transaction_type']);
                $table->index(['user_id', 'product_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('v2_product_transactions');
        Schema::dropIfExists('v2_products');

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'created_by')) {
                $table->dropForeign(['created_by']);
                $table->dropColumn('created_by');
            }
            if (Schema::hasColumn('users', 'entitlements')) {
                $table->dropColumn('entitlements');
            }
            if (Schema::hasColumn('users', 'is_platform_admin')) {
                $table->dropColumn('is_platform_admin');
            }
        });
    }
};
