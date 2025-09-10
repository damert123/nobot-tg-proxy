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
        Schema::table('telegram_accounts', function (Blueprint $table) {
            $table->unsignedInteger('message_rate')->default(0)->after('token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('telegram_accounts', function (Blueprint $table) {
            $table->dropColumn('message_rate');
        });
    }
};
