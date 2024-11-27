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
        Schema::create('planfix_integrations', function (Blueprint $table) {
            $table->id();
            $table->string('provider_id')->unique();
            $table->string('planfix_token')->unique();
            $table->string('name');
            $table->string('token')->unique();
            $table->foreignId('telegram_account_id')->constrained('telegram_accounts');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('planfix_integrations');
    }
};
