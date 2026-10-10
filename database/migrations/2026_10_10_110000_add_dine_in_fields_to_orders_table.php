<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'order_type')) {
                $table->string('order_type', 30)->default('delivery')->after('daily_order_number');
            }
            if (!Schema::hasColumn('orders', 'table_number')) {
                $table->string('table_number', 50)->nullable()->after('order_type');
            }
            // Broaden status column from enum to string so dine-in statuses (e.g. 'served') are supported across all databases
            $table->string('status', 40)->default('pending')->change();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'table_number')) {
                $table->dropColumn('table_number');
            }
            if (Schema::hasColumn('orders', 'order_type')) {
                $table->dropColumn('order_type');
            }
        });
    }
};
