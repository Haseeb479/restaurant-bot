<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            if (! Schema::hasColumn('restaurants', 'email_verified_at')) {
                $table->timestamp('email_verified_at')->nullable()->after('email');
            }
            if (! Schema::hasColumn('restaurants', 'verification_token_hash')) {
                $table->string('verification_token_hash', 64)->nullable()->after('email_verified_at');
            }
            if (! Schema::hasColumn('restaurants', 'verification_sent_at')) {
                $table->timestamp('verification_sent_at')->nullable()->after('verification_token_hash');
            }
        });

        // Pre-existing active restaurants are marked verified to avoid breaking current accounts
        \Illuminate\Support\Facades\DB::table('restaurants')
            ->where('is_active', true)
            ->whereNull('email_verified_at')
            ->update(['email_verified_at' => \Illuminate\Support\Facades\DB::raw('created_at')]);
    }

    public function down(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $columns = ['email_verified_at', 'verification_token_hash', 'verification_sent_at'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('restaurants', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
