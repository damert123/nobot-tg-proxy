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
        Schema::create('tg_to_crm_messages', function (Blueprint $table) {
            $table->id();
            $table->string('provider_id');
            $table->unsignedBigInteger('chat_id');
            $table->string('planfix_token');
            $table->text('message');
            $table->string('title')->nullable();
            $table->unsignedBigInteger('contact_id');
            $table->string('contact_name')->nullable();
            $table->string('contact_last_name')->nullable();
            $table->string('telegram_username')->nullable();
            $table->string('contact_data')->nullable();
            $table->string('attachments_name')->nullable();
            $table->string('attachments_url')->nullable();
            $table->string('status')->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tg_to_crm_messages');
    }
};
