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
        Schema::create('call_campaign_lead_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('call_campaign_lead_id')->index();
            $table->unsignedTinyInteger('step')->index();
            $table->text('message_template')->nullable();
            $table->timestamp('scheduled_at')->nullable()->index();
            $table->timestamp('sent_at')->nullable();
            $table->string('status')->default('pending')->index();
            $table->text('sent_message')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('call_campaign_lead_messages');
    }
};
