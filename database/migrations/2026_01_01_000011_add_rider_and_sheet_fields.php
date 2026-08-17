<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('rider_name')->nullable()->after('estimated_minutes');
            $table->string('rider_phone')->nullable()->after('rider_name');
            $table->string('rider_notes')->nullable()->after('rider_phone');
        });

        Schema::table('restaurants', function (Blueprint $table) {
            $table->string('google_sheet_webhook')->nullable()->after('greeting_message');
            $table->string('manager_phone')->nullable()->after('owner_phone');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['rider_name', 'rider_phone', 'rider_notes']);
        });

        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropColumn(['google_sheet_webhook', 'manager_phone']);
        });
    }
};
