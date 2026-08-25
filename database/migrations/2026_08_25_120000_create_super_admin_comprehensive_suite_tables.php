<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Subscription Plans (Pricing Tiers)
        if (!Schema::hasTable('subscription_plans')) {
            Schema::create('subscription_plans', function (Blueprint $table) {
                $table->id();
                $table->string('name'); // Starter, Pro, Enterprise
                $table->string('slug')->unique();
                $table->decimal('price_monthly', 10, 2)->default(0);
                $table->decimal('price_yearly', 10, 2)->default(0);
                $table->integer('max_orders_per_month')->default(500);
                $table->integer('max_menu_items')->default(50);
                $table->json('features')->nullable(); // feature flags enabled in this tier
                $table->boolean('is_active')->default(true);
                $table->boolean('is_popular')->default(false);
                $table->timestamps();
            });
        }

        // 2. Invoices & Billing Ledger
        if (!Schema::hasTable('invoices')) {
            Schema::create('invoices', function (Blueprint $table) {
                $table->id();
                $table->string('invoice_number')->unique();
                $table->foreignId('restaurant_id')->nullable()->constrained('restaurants')->nullOnDelete();
                $table->string('plan_name')->default('Pro');
                $table->decimal('amount', 10, 2);
                $table->string('currency')->default('PKR');
                $table->string('payment_method')->default('JazzCash'); // JazzCash, EasyPaisa, Bank Transfer, Cash
                $table->string('payment_reference')->nullable(); // transaction ID, receipt #
                $table->string('status')->default('paid'); // paid, unpaid, overdue, refunded, void
                $table->date('due_date')->nullable();
                $table->date('paid_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // 3. Support Tickets & Conversations
        if (!Schema::hasTable('support_tickets')) {
            Schema::create('support_tickets', function (Blueprint $table) {
                $table->id();
                $table->string('ticket_id')->unique();
                $table->foreignId('restaurant_id')->nullable()->constrained('restaurants')->nullOnDelete();
                $table->string('contact_name')->nullable();
                $table->string('contact_phone')->nullable();
                $table->string('subject');
                $table->text('description');
                $table->string('priority')->default('medium'); // low, medium, high, urgent
                $table->string('status')->default('open'); // open, in_progress, resolved, closed
                $table->text('internal_notes')->nullable();
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('support_ticket_messages')) {
            Schema::create('support_ticket_messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('ticket_id')->constrained('support_tickets')->cascadeOnDelete();
                $table->enum('sender_type', ['admin', 'restaurant', 'customer'])->default('admin');
                $table->string('sender_name')->default('Super Admin');
                $table->text('message');
                $table->timestamps();
            });
        }

        // 4. Platform Announcements
        if (!Schema::hasTable('announcements')) {
            Schema::create('announcements', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->text('content');
                $table->string('type')->default('info'); // info, warning, success, maintenance
                $table->string('target')->default('all'); // all, specific_plan, active_only
                $table->boolean('is_active')->default(true);
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
            });
        }

        // 5. Spam / Abuse Blacklisted Numbers
        if (!Schema::hasTable('blacklisted_numbers')) {
            Schema::create('blacklisted_numbers', function (Blueprint $table) {
                $table->id();
                $table->string('phone_number')->unique();
                $table->string('reason')->nullable();
                $table->string('reported_by')->default('Super Admin');
                $table->timestamps();
            });
        }

        // 6. User / Restaurant Feedback
        if (!Schema::hasTable('feedbacks')) {
            Schema::create('feedbacks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('restaurant_id')->nullable()->constrained('restaurants')->nullOnDelete();
                $table->string('user_name')->nullable();
                $table->string('user_phone')->nullable();
                $table->unsignedTinyInteger('rating')->default(5); // 1-5 stars
                $table->text('comment')->nullable();
                $table->string('category')->default('general'); // bot, menu, delivery, service
                $table->boolean('is_reviewed')->default(false);
                $table->timestamps();
            });
        }

        // 7. Audit & Activity Logs
        if (!Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function (Blueprint $table) {
                $table->id();
                $table->string('action'); // restaurant.created, plan.extended, credentials.reset, etc.
                $table->string('actor_name')->default('Super Admin');
                $table->string('ip_address')->nullable();
                $table->string('user_agent')->nullable();
                $table->text('details')->nullable();
                $table->timestamps();
            });
        }

        // 8. Global Menu Templates
        if (!Schema::hasTable('menu_templates')) {
            Schema::create('menu_templates', function (Blueprint $table) {
                $table->id();
                $table->string('name'); // e.g. "Fast Food & Burgers", "Pizza & Pasta", "Desi BBQ & Karahi", "Cafe & Desserts"
                $table->string('cuisine_type')->default('Fast Food');
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('menu_template_items')) {
            Schema::create('menu_template_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('menu_template_id')->constrained('menu_templates')->cascadeOnDelete();
                $table->string('category_name');
                $table->string('item_name');
                $table->decimal('price', 10, 2)->default(0);
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        // 9. API Keys for External Integrations
        if (!Schema::hasTable('api_keys')) {
            Schema::create('api_keys', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('key')->unique();
                $table->json('permissions')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamp('last_used_at')->nullable();
                $table->timestamps();
            });
        }

        // 10. Extend restaurants table with registration status, features, and config
        Schema::table('restaurants', function (Blueprint $table) {
            if (!Schema::hasColumn('restaurants', 'status')) {
                $table->string('status')->default('active')->after('is_active'); // pending, active, suspended, rejected
            }
            if (!Schema::hasColumn('restaurants', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('status');
            }
            if (!Schema::hasColumn('restaurants', 'features')) {
                $table->json('features')->nullable()->after('rejection_reason');
            }
            if (!Schema::hasColumn('restaurants', 'ai_config')) {
                $table->json('ai_config')->nullable()->after('features');
            }
            if (!Schema::hasColumn('restaurants', 'rate_limit_per_month')) {
                $table->integer('rate_limit_per_month')->default(1000)->after('ai_config');
            }
            if (!Schema::hasColumn('restaurants', 'api_key')) {
                $table->string('api_key')->nullable()->after('rate_limit_per_month');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_keys');
        Schema::dropIfExists('menu_template_items');
        Schema::dropIfExists('menu_templates');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('feedbacks');
        Schema::dropIfExists('blacklisted_numbers');
        Schema::dropIfExists('announcements');
        Schema::dropIfExists('support_ticket_messages');
        Schema::dropIfExists('support_tickets');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('subscription_plans');

        Schema::table('restaurants', function (Blueprint $table) {
            $columns = ['status', 'rejection_reason', 'features', 'ai_config', 'rate_limit_per_month', 'api_key'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('restaurants', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
