<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use App\Models\MenuTemplate;
use App\Models\MenuTemplateItem;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class SuperAdminDefaultsSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed Default Subscription Plans (in PKR)
        if (SubscriptionPlan::count() === 0) {
            SubscriptionPlan::create([
                'name'                 => 'Starter (Trial)',
                'slug'                 => 'starter',
                'price_monthly'        => 0,
                'price_yearly'         => 0,
                'max_orders_per_month' => 150,
                'max_menu_items'       => 30,
                'features'             => [
                    'order_tracking'        => true,
                    'customer_notifications'=> true,
                    'ai_suggestions'        => false,
                    'human_handover'        => true,
                    'voice_notes'           => false,
                    'deal_broadcast'        => false,
                ],
                'is_active'            => true,
                'is_popular'           => false,
            ]);

            SubscriptionPlan::create([
                'name'                 => 'Pro Business',
                'slug'                 => 'pro',
                'price_monthly'        => 3500,
                'price_yearly'         => 35000,
                'max_orders_per_month' => 1500,
                'max_menu_items'       => 150,
                'features'             => [
                    'order_tracking'        => true,
                    'customer_notifications'=> true,
                    'ai_suggestions'        => true,
                    'human_handover'        => true,
                    'voice_notes'           => true,
                    'deal_broadcast'        => true,
                ],
                'is_active'            => true,
                'is_popular'           => true,
            ]);

            SubscriptionPlan::create([
                'name'                 => 'Enterprise Suite',
                'slug'                 => 'enterprise',
                'price_monthly'        => 8000,
                'price_yearly'         => 80000,
                'max_orders_per_month' => 10000,
                'max_menu_items'       => 500,
                'features'             => [
                    'order_tracking'        => true,
                    'customer_notifications'=> true,
                    'ai_suggestions'        => true,
                    'human_handover'        => true,
                    'voice_notes'           => true,
                    'deal_broadcast'        => true,
                    'custom_ai_prompts'     => true,
                    'priority_support'      => true,
                ],
                'is_active'            => true,
                'is_popular'           => false,
            ]);
        }

        // 2. Seed Default Global Menu Templates
        if (MenuTemplate::count() === 0) {
            $fastFood = MenuTemplate::create([
                'name'         => 'Fast Food & Burgers',
                'cuisine_type' => 'Fast Food',
                'description'  => 'Standard burgers, loaded fries, crispy wings, wraps, and beverages.',
            ]);

            $fastFoodItems = [
                ['category_name' => 'Burgers', 'item_name' => 'Zinger Burger (Crispy Chicken)', 'price' => 450, 'description' => 'Crispy fried fillet, iceberg lettuce, signature mayo.'],
                ['category_name' => 'Burgers', 'item_name' => 'Beef Smash Cheese Burger', 'price' => 650, 'description' => 'Double smashed beef patties with cheddar cheese and smoky sauce.'],
                ['category_name' => 'Sides', 'item_name' => 'Loaded Cheese Fries', 'price' => 380, 'description' => 'Crispy french fries topped with warm cheese sauce and jalapeños.'],
                ['category_name' => 'Sides', 'item_name' => 'Spicy Buffalo Wings (6 pcs)', 'price' => 420, 'description' => 'Crispy wings tossed in fiery red buffalo sauce.'],
                ['category_name' => 'Drinks', 'item_name' => 'Soft Drink (500ml Can)', 'price' => 120, 'description' => 'Chilled soft drink choice.'],
            ];

            foreach ($fastFoodItems as $item) {
                MenuTemplateItem::create(array_merge($item, ['menu_template_id' => $fastFood->id]));
            }

            $desi = MenuTemplate::create([
                'name'         => 'Desi BBQ & Karahi',
                'cuisine_type' => 'Pakistani / Desi',
                'description'  => 'Traditional chicken/mutton karahi, seekh kababs, tikkas, naan, and raita.',
            ]);

            $desiItems = [
                ['category_name' => 'Karahi Special', 'item_name' => 'Chicken Peshawari Karahi (Half kg)', 'price' => 1100, 'description' => 'Fresh tender chicken cooked with tomatoes, green chillies and ginger.'],
                ['category_name' => 'BBQ', 'item_name' => 'Chicken Tikka Boti Plate (8 pcs)', 'price' => 550, 'description' => 'Charcoal-grilled juicy marinated chicken chunks with mint raita.'],
                ['category_name' => 'BBQ', 'item_name' => 'Beef Seekh Kabab (4 pcs)', 'price' => 580, 'description' => 'Minced beef blended with authentic spices and herbs.'],
                ['category_name' => 'Breads', 'item_name' => 'Roghnai Naan', 'price' => 80, 'description' => 'Fluffy traditional naan with sesame seeds and butter glaze.'],
            ];

            foreach ($desiItems as $item) {
                MenuTemplateItem::create(array_merge($item, ['menu_template_id' => $desi->id]));
            }

            $pizza = MenuTemplate::create([
                'name'         => 'Pizzeria & Pasta',
                'cuisine_type' => 'Italian / Pizza',
                'description'  => 'Artisan pizzas, creamy pastas, garlic breads, and beverages.',
            ]);

            $pizzaItems = [
                ['category_name' => 'Pizza', 'item_name' => 'Chicken Fajita Pizza (Large 13")', 'price' => 1450, 'description' => 'Fajita chicken, capsicum, onions, mozzarella cheese and pizza sauce.'],
                ['category_name' => 'Pizza', 'item_name' => 'Crown Crust Pepperoni Pizza (Large)', 'price' => 1650, 'description' => 'Pepperoni slices, double mozzarella with cheese stuffed crown crust.'],
                ['category_name' => 'Pasta', 'item_name' => 'Fettuccine Alfredo Pasta', 'price' => 750, 'description' => 'Fettuccine in rich parmesan garlic cream sauce with grilled chicken.'],
                ['category_name' => 'Sides', 'item_name' => 'Garlic Bread with Cheese (4 pcs)', 'price' => 290, 'description' => 'Toasted baguette with garlic butter and melted mozzarella.'],
            ];

            foreach ($pizzaItems as $item) {
                MenuTemplateItem::create(array_merge($item, ['menu_template_id' => $pizza->id]));
            }
        }

        // 3. Default Pakistani Payment Details & Settings
        Setting::put('payment_jazzcash_title', Setting::get('payment_jazzcash_title', 'Restaurant Bot Services'));
        Setting::put('payment_jazzcash_number', Setting::get('payment_jazzcash_number', '03001234567'));
        Setting::put('payment_easypaisa_title', Setting::get('payment_easypaisa_title', 'Restaurant Bot Services'));
        Setting::put('payment_easypaisa_number', Setting::get('payment_easypaisa_number', '03451234567'));
        Setting::put('payment_bank_name', Setting::get('payment_bank_name', 'Meezan Bank Ltd'));
        Setting::put('payment_bank_title', Setting::get('payment_bank_title', 'Restaurant Bot Tech PVT'));
        Setting::put('payment_bank_account', Setting::get('payment_bank_account', '01020304050607'));
        Setting::put('payment_bank_iban', Setting::get('payment_bank_iban', 'PK00MEZN0001020304050607'));
        Setting::put('currency_symbol', Setting::get('currency_symbol', 'Rs.'));
        Setting::put('currency_code', Setting::get('currency_code', 'PKR'));
        Setting::put('platform_timezone', Setting::get('platform_timezone', 'Asia/Karachi'));
        Setting::put('ai_model_default', Setting::get('ai_model_default', 'gemini-1.5-flash'));
        Setting::put('ai_temperature', Setting::get('ai_temperature', '0.7'));
    }
}
