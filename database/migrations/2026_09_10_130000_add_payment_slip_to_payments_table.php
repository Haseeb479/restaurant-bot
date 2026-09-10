<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('payments') && !Schema::hasColumn('payments', 'payment_slip')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->string('payment_slip')->nullable()->after('payment_reference');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('payments') && Schema::hasColumn('payments', 'payment_slip')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropColumn('payment_slip');
            });
        }
    }
};
