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
        Schema::create('queued_service_messages', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('telegram_id');
            $table->text('message');
            $table->string('telegram_link');
            $table->string('status')->default('pending');
            $table->timestamp('scheduled_at');
            $table->timestamps();

            $table->index(['status', 'scheduled_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('queue_service_messages');
    }
};
