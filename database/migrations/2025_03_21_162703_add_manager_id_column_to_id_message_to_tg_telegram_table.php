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
        Schema::table('id_message_to_tg_telegram', function (Blueprint $table) {
            $table->bigInteger('manager_id')->after('message_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('id_message_to_tg_telegram', function (Blueprint $table) {
            $table->dropColumn('manager_id');
        });
    }
};
