<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('v2_inspiration_posts')) {
            return;
        }

        Schema::table('v2_inspiration_posts', function (Blueprint $table) {
            if (! Schema::hasColumn('v2_inspiration_posts', 'is_favorite')) {
                $table->boolean('is_favorite')->default(false)->after('content');
            }
            if (! Schema::hasColumn('v2_inspiration_posts', 'category')) {
                $table->string('category', 50)->nullable()->after('is_favorite');
            }
            if (! Schema::hasColumn('v2_inspiration_posts', 'engagement')) {
                $table->unsignedInteger('engagement')->default(0)->after('category');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('v2_inspiration_posts')) {
            return;
        }

        Schema::table('v2_inspiration_posts', function (Blueprint $table) {
            $columns = [];
            foreach (['engagement', 'category', 'is_favorite'] as $col) {
                if (Schema::hasColumn('v2_inspiration_posts', $col)) {
                    $columns[] = $col;
                }
            }
            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
