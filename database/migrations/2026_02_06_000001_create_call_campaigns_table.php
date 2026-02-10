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
        Schema::create('call_campaigns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->bigInteger('audience_id')->index();
            $table->string('name');
            $table->string('status')->default('active');
            $table->text('message_one');
            $table->text('message_two');
            $table->text('message_three');
            $table->integer('delay_two_minutes')->default(60);
            $table->integer('delay_three_minutes')->default(1440);
            $table->timestamp('starts_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('call_campaigns');
    }
};
