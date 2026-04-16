<?php

namespace Database\Seeders;

use App\Models\Restaurant;
use App\Models\Category;
use App\Models\MenuItem;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $r = Restaurant::updateOrCreate([
            'whatsapp_number' => '+923001234567',
        ], [
            'name'             => 'Taste of Bahawalpur',
            'owner_phone'      => '+923009876543',
            'owner_password'   => 'test123',
            'city'             => 'Bahawalpur',
            'address'          => 'Shop 5, Satellite Town, Bahawalpur',
            'delivery_areas'   => 'Satellite Town, Model Town, City Centre, Cantt',
            'delivery_charge'  => 50,
            'minimum_order'    => 300,
            'is_active'        => true,
            'is_open'          => true,
            'plan'             => 'trial',
            'plan_expires_at'  => null,
            'greeting_message' => 'Assalam o Alaikum! Khush Amdeed!',
        ]);

        $burgers = Category::updateOrCreate(['restaurant_id' => $r->id, 'name' => 'Burgers'],        ['sort_order' => 1]);
        $biryani = Category::updateOrCreate(['restaurant_id' => $r->id, 'name' => 'Biryani & Rice'], ['sort_order' => 2]);
        $drinks  = Category::updateOrCreate(['restaurant_id' => $r->id, 'name' => 'Drinks'],         ['sort_order' => 3]);
        $deals   = Category::updateOrCreate(['restaurant_id' => $r->id, 'name' => 'Deals'],          ['sort_order' => 4]);

        $burgerItems = [
            ['name' => 'Zinger Burger', 'price' => 350, 'description' => 'Crispy chicken fillet'],
            ['name' => 'Beef Burger',   'price' => 400, 'description' => 'Juicy beef patty'],
            ['name' => 'Double Zinger', 'price' => 550, 'description' => 'Double the taste'],
            ['name' => 'Veggie Burger', 'price' => 250, 'description' => 'Fresh vegetables'],
        ];
        foreach ($burgerItems as $i => $item) {
            MenuItem::updateOrCreate([
                'restaurant_id' => $r->id,
                'category_id'   => $burgers->id,
                'name'          => $item['name'],
            ], [
                'description'  => $item['description'],
                'price'        => $item['price'],
                'is_available' => true,
                'sort_order'   => $i,
            ]);
        }

        $biryaniItems = [
            ['name' => 'Chicken Biryani', 'price' => 280, 'description' => 'Full plate'],
            ['name' => 'Beef Biryani',    'price' => 320, 'description' => 'Full plate'],
            ['name' => 'Half Biryani',    'price' => 180, 'description' => 'Half plate'],
            ['name' => 'Mutton Pulao',    'price' => 220, 'description' => 'Mutton pulao'],
        ];
        foreach ($biryaniItems as $i => $item) {
            MenuItem::updateOrCreate([
                'restaurant_id' => $r->id,
                'category_id'   => $biryani->id,
                'name'          => $item['name'],
            ], [
                'description'  => $item['description'],
                'price'        => $item['price'],
                'is_available' => true,
                'sort_order'   => $i,
            ]);
        }

        $drinkItems = [
            ['name' => 'Pepsi 500ml',   'price' => 80,  'description' => 'Ice cold'],
            ['name' => '7Up 500ml',     'price' => 80,  'description' => 'Ice cold'],
            ['name' => 'Lassi',         'price' => 120, 'description' => 'Sweet or salty'],
            ['name' => 'Mineral Water', 'price' => 50,  'description' => '500ml bottle'],
        ];
        foreach ($drinkItems as $i => $item) {
            MenuItem::updateOrCreate([
                'restaurant_id' => $r->id,
                'category_id'   => $drinks->id,
                'name'          => $item['name'],
            ], [
                'description'  => $item['description'],
                'price'        => $item['price'],
                'is_available' => true,
                'sort_order'   => $i,
            ]);
        }

        $dealItems = [
            ['name' => 'Family Deal A', 'price' => 1200, 'description' => '2 Zingers + Biryani + 2 Drinks'],
            ['name' => 'Couple Deal',   'price' => 750,  'description' => '2 Burgers + 2 Drinks'],
            ['name' => 'Solo Deal',     'price' => 450,  'description' => '1 Burger + 1 Drink'],
        ];
        foreach ($dealItems as $i => $item) {
            MenuItem::updateOrCreate([
                'restaurant_id' => $r->id,
                'category_id'   => $deals->id,
                'name'          => $item['name'],
            ], [
                'description'  => $item['description'],
                'price'        => $item['price'],
                'is_available' => true,
                'sort_order'   => $i,
            ]);
        }

        $this->command->info('');
        $this->command->info('Sample restaurant created!');
        $this->command->info('Dashboard URL : /dashboard/' . $r->id . '/login');
        $this->command->info('Password      : test123');
        $this->command->info('Admin URL     : /admin');
        $this->command->info('');
    }
}