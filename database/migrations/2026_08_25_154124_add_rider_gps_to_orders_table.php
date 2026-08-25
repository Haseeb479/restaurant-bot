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
        Schema::table('orders', function (Blueprint $table) {
            $table->string('rider_token', 64)->nullable()->unique()->after('rider_notes');
            $table->decimal('rider_lat', 10, 7)->nullable()->after('rider_token');
            $table->decimal('rider_lng', 10, 7)->nullable()->after('rider_lat');
            $table->timestamp('rider_location_updated_at')->nullable()->after('rider_lng');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'rider_token',
                'rider_lat',
                'rider_lng',
                'rider_location_updated_at',
            ]);
        });
    }
};
