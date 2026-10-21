<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('subscription_plans')) {
            return;
        }

        $now = now();

        $plans = [
            [
                'slug'                 => 'starter',
                'name'                 => 'Cloud POS Core',
                'price_monthly'        => 0.00,
                'price_yearly'         => 0.00,
                'max_orders_per_month' => 500,
                'max_menu_items'       => 50,
                'features'             => json_encode([
                    '1 Active Cloud POS Terminal',
                    'Digital Dine-in QR Menu',
                    'Thermal Receipt Printer Sync',
                    '14-Day Free Trial'
                ]),
                'is_active'            => 1,
                'is_popular'           => 0,
                'created_at'           => $now,
                'updated_at'           => $now,
            ],
            [
                'slug'                 => 'pro',
                'name'                 => 'POS + WhatsApp Pro',
                'price_monthly'        => 8000.00,
                'price_yearly'         => 80000.00,
                'max_orders_per_month' => 5000,
                'max_menu_items'       => 250,
                'features'             => json_encode([
                    'Unlimited POS Terminals',
                    'Official WhatsApp Ordering Bot',
                    'Table Seating & Kitchen KDS',
                    'Automated Customer Re-Order Alerts'
                ]),
                'is_active'            => 1,
                'is_popular'           => 1,
                'created_at'           => $now,
                'updated_at'           => $now,
            ],
            [
                'slug'                 => 'enterprise',
                'name'                 => 'Multi-Outlet OS',
                'price_monthly'        => 16000.00,
                'price_yearly'         => 160000.00,
                'max_orders_per_month' => 50000,
                'max_menu_items'       => 1000,
                'features'             => json_encode([
                    'Multi-branch central inventory sync',
                    'Custom WhatsApp AI Persona',
                    'Dedicated Server & SLA Guarantee'
                ]),
                'is_active'            => 1,
                'is_popular'           => 0,
                'created_at'           => $now,
                'updated_at'           => $now,
            ],
        ];

        foreach ($plans as $planData) {
            $existing = DB::table('subscription_plans')->where('slug', $planData['slug'])->first();
            if ($existing) {
                DB::table('subscription_plans')->where('slug', $planData['slug'])->update([
                    'name'                 => $planData['name'],
                    'price_monthly'        => $planData['price_monthly'],
                    'price_yearly'         => $planData['price_yearly'],
                    'max_orders_per_month' => $planData['max_orders_per_month'],
                    'max_menu_items'       => $planData['max_menu_items'],
                    'features'             => $planData['features'],
                    'is_active'            => $planData['is_active'],
                    'is_popular'           => $planData['is_popular'],
                    'updated_at'           => $now,
                ]);
            } else {
                DB::table('subscription_plans')->insert($planData);
            }
        }
    }

    public function down(): void
    {
        // Keep rates intact
    }
};
