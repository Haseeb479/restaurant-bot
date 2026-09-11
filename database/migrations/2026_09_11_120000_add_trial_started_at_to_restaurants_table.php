<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            if (! Schema::hasColumn('restaurants', 'trial_started_at')) {
                $table->timestamp('trial_started_at')->nullable()->after('plan_expires_at');
            }
        });

        // Backfill existing trial restaurants with their created_at timestamp
        \Illuminate\Support\Facades\DB::table('restaurants')
            ->where('plan', 'trial')
            ->whereNull('trial_started_at')
            ->update(['trial_started_at' => \Illuminate\Support\Facades\DB::raw('created_at')]);
    }

    public function down(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            if (Schema::hasColumn('restaurants', 'trial_started_at')) {
                $table->dropColumn('trial_started_at');
            }
        });
    }
};
