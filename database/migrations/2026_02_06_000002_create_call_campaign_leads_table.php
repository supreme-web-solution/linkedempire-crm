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
        Schema::create('call_campaign_leads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('call_campaign_id')->index();
            $table->unsignedBigInteger('audience_list_id')->nullable()->index();
            $table->unsignedBigInteger('call_status_id')->nullable()->index();
            $table->string('recipient')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('title')->nullable();
            $table->string('company')->nullable();
            $table->string('location')->nullable();
            $table->string('connection_id')->nullable()->index();
            $table->string('public_identifier')->nullable();
            $table->string('profile_url')->nullable();
            $table->string('member_urn')->nullable();
            $table->string('status')->default('active');
            $table->timestamp('last_message_sent_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('call_campaign_leads');
    }
};
