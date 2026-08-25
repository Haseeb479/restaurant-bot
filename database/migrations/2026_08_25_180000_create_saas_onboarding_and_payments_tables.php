<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add onboarding, plan, and payment tracking fields to restaurants table
        Schema::table('restaurants', function (Blueprint $table) {
            if (!Schema::hasColumn('restaurants', 'owner_name')) {
                $table->string('owner_name')->nullable()->after('name');
            }
            if (!Schema::hasColumn('restaurants', 'email')) {
                $table->string('email')->nullable()->after('owner_name');
            }
            if (!Schema::hasColumn('restaurants', 'plan_id')) {
                $table->foreignId('plan_id')->nullable()->after('plan')->constrained('subscription_plans')->nullOnDelete();
            }
            if (!Schema::hasColumn('restaurants', 'payment_status')) {
                $table->string('payment_status')->default('pending')->after('plan_id'); // pending, completed, failed, refunded
            }
            if (!Schema::hasColumn('restaurants', 'registration_status')) {
                $table->string('registration_status')->default('pending_review')->after('payment_status'); // pending_plan, pending_payment, pending_review, approved, rejected
            }
            if (!Schema::hasColumn('restaurants', 'payment_id')) {
                $table->string('payment_id')->nullable()->after('registration_status');
            }
            if (!Schema::hasColumn('restaurants', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('rejection_reason');
            }
        });

        // 2. Create payments table
        if (!Schema::hasTable('payments')) {
            Schema::create('payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('restaurant_id')->constrained('restaurants')->cascadeOnDelete();
                $table->foreignId('plan_id')->nullable()->constrained('subscription_plans')->nullOnDelete();
                $table->decimal('amount', 10, 2);
                $table->string('currency')->default('PKR');
                $table->string('payment_method'); // stripe, jazzcash, easypaisa, bank_transfer
                $table->string('payment_reference')->nullable();
                $table->string('stripe_payment_id')->nullable();
                $table->string('status')->default('pending'); // pending, completed, failed, refunded
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
            });
        }

        // 3. Create subscriptions table
        if (!Schema::hasTable('subscriptions')) {
            Schema::create('subscriptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('restaurant_id')->constrained('restaurants')->cascadeOnDelete();
                $table->foreignId('plan_id')->constrained('subscription_plans')->cascadeOnDelete();
                $table->string('status')->default('active'); // active, cancelled, expired, paused
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('payments');

        Schema::table('restaurants', function (Blueprint $table) {
            $cols = ['owner_name', 'email', 'plan_id', 'payment_status', 'registration_status', 'payment_id', 'approved_at'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('restaurants', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
