<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\MenuItem;
use App\Models\MenuItemVariant;
use App\Models\Restaurant;
use App\Models\RestaurantApiToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class MobilePosApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_pos_calculates_item_prices_authoritatively()
    {
        $restaurant = new Restaurant([
            'name'            => 'Burger Shack',
            'whatsapp_number' => '923007778899',
            'wa_phone_id'     => 'wa_pos',
            'owner_phone'     => '923007778899',
            'city'            => 'Islamabad',
            'address'         => 'F-7',
            'is_active'       => true,
            'delivery_charge' => 150,
        ]);
        $restaurant->owner_password = Hash::make('pass');
        $restaurant->save();

        $category = Category::create([
            'restaurant_id' => $restaurant->id,
            'name'          => 'Burgers',
            'sort_order'    => 1,
        ]);

        $item = MenuItem::create([
            'restaurant_id' => $restaurant->id,
            'category_id'   => $category->id,
            'name'          => 'Zinger Burger',
            'price'         => 600,
            'is_available'  => true,
        ]);

        $variant = MenuItemVariant::create([
            'menu_item_id' => $item->id,
            'name'         => 'Double Patty',
            'price'        => 850,
            'is_active'    => true,
        ]);

        $token = Str::random(60);
        RestaurantApiToken::create([
            'restaurant_id' => $restaurant->id,
            'token'         => hash('sha256', $token),
        ]);

        // 1. Get POS catalog
        $catalogResp = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/pos/catalog');

        $catalogResp->assertStatus(200);
        $this->assertCount(1, $catalogResp->json('categories'));

        // 2. Create POS order (server must calculate 2 * 850 + 150 = 1850)
        $orderResp = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/pos/orders', [
                'customer_name'    => 'Ali Khan',
                'customer_phone'   => '923112233445',
                'delivery_type'    => 'delivery',
                'delivery_address' => 'Street 10, F-7/2',
                'payment_method'   => 'cash_on_delivery',
                'items'            => [
                    [
                        'item_id'    => $item->id,
                        'variant_id' => $variant->id,
                        'quantity'   => 2,
                    ],
                ],
            ]);

        $orderResp->assertStatus(201)
            ->assertJson([
                'success' => true,
                'order'   => [
                    'restaurant_id'   => $restaurant->id,
                    'subtotal'        => 1700,
                    'delivery_charge' => 150,
                    'total'           => 1850,
                ],
            ]);
    }
}
