<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->index('customer_phone', 'orders_customer_phone_idx');
            $table->index('status', 'orders_status_idx');
            $table->index('created_at', 'orders_created_at_idx');
            $table->index(['restaurant_id', 'status'], 'orders_restaurant_status_idx');
            $table->index(['restaurant_id', 'created_at'], 'orders_restaurant_created_at_idx');
        });

        if (Schema::hasTable('conversations')) {
            Schema::table('conversations', function (Blueprint $table) {
                $table->index('customer_phone', 'conversations_customer_phone_idx');
                $table->index(['restaurant_id', 'updated_at'], 'conversations_restaurant_updated_idx');
            });
        }

        if (Schema::hasTable('audit_logs')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->index('created_at', 'audit_logs_created_at_idx');
                $table->index('action', 'audit_logs_action_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_customer_phone_idx');
            $table->dropIndex('orders_status_idx');
            $table->dropIndex('orders_created_at_idx');
            $table->dropIndex('orders_restaurant_status_idx');
            $table->dropIndex('orders_restaurant_created_at_idx');
        });

        if (Schema::hasTable('conversations')) {
            Schema::table('conversations', function (Blueprint $table) {
                $table->dropIndex('conversations_customer_phone_idx');
                $table->dropIndex('conversations_restaurant_updated_idx');
            });
        }

        if (Schema::hasTable('audit_logs')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->dropIndex('audit_logs_created_at_idx');
                $table->dropIndex('audit_logs_action_idx');
            });
        }
    }
};
