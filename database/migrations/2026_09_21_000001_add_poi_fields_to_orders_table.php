<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('delivery_place_name')->nullable()->after('delivery_lng');
            $table->string('delivery_place_id')->nullable()->after('delivery_place_name');
            $table->string('location_source', 50)->nullable()->after('delivery_place_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['delivery_place_name', 'delivery_place_id', 'location_source']);
        });
    }
};
