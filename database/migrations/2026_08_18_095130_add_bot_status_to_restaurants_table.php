<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            // WhatsApp Bot status tracking
            $table->string('bot_status')->default('disconnected')->after('is_active');
            // Values: 'connected', 'disconnected', 'qr_expired', 'qr_pending'
            $table->timestamp('bot_last_seen_at')->nullable()->after('bot_status');

            // Soft deactivation tracking (never hard delete)
            $table->timestamp('deactivated_at')->nullable()->after('bot_last_seen_at');
            $table->string('deactivated_reason')->nullable()->after('deactivated_at');

            // Error log tracking: last error per restaurant
            $table->text('last_error')->nullable()->after('deactivated_reason');
            $table->timestamp('last_error_at')->nullable()->after('last_error');
        });
    }

    public function down(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropColumn(['bot_status', 'bot_last_seen_at', 'deactivated_at', 'deactivated_reason', 'last_error', 'last_error_at']);
        });
    }
};
