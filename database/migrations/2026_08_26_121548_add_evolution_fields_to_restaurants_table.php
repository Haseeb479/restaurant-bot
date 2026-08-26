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
        Schema::table('restaurants', function (Blueprint $table) {
            $table->string('evolution_instance_id', 100)->nullable()->after('api_key')->index();
            $table->string('evolution_status', 30)->default('disconnected')->after('evolution_instance_id');
            $table->string('bot_phone_number', 35)->nullable()->after('evolution_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropColumn([
                'evolution_instance_id',
                'evolution_status',
                'bot_phone_number',
            ]);
        });
    }
};
