<?php

namespace Tests\Feature;

use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SecurityPhase2And3RbacTest extends TestCase
{
    use RefreshDatabase;

    private function createRestaurant(string $name = 'Grill Point', bool $active = true): Restaurant
    {
        $r = new Restaurant([
            'name'                => $name,
            'whatsapp_number'     => '92300' . random_int(1000000, 9999999),
            'owner_phone'         => '92300' . random_int(1000000, 9999999),
            'status'              => $active ? 'active' : 'pending',
            'registration_status' => $active ? 'approved' : 'pending_review',
            'is_open'             => true,
            'plan'                => 'trial',
        ]);
        $r->is_active = $active;
        $r->owner_password = Hash::make('Secret123');
        $r->save();

        return $r;
    }

    // ── NAV-001: PreventBackHistory Security Headers ────────────────

    public function test_nav_001_web_responses_contain_no_store_cache_control_headers(): void
    {
        $r = $this->createRestaurant('Cache Test Kitchen');
        $this->withSession(["restaurant_{$r->id}" => true]);

        $response = $this->get(route('dashboard.orders', $r->id));
        $response->assertOk();

        $cacheControl = $response->headers->get('Cache-Control');
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('no-cache', $cacheControl);
        $this->assertStringContainsString('must-revalidate', $cacheControl);
    }

    // ── RBAC-001: Super Admin Route Protection from Owners & Guests ──

    public function test_rbac_001_owner_cannot_access_super_admin_dashboard_or_endpoints(): void
    {
        $r = $this->createRestaurant('Owner Restaurant');
        $this->withSession(["restaurant_{$r->id}" => true]);

        $this->get(route('admin.dashboard'))->assertForbidden();
        $this->get(route('admin.restaurants'))->assertForbidden();
        $this->get(route('admin.billing'))->assertForbidden();
        $this->get(route('admin.system-health'))->assertForbidden();
        $this->get(route('admin.api-keys'))->assertForbidden();
        $this->get(route('admin.audit-logs'))->assertForbidden();
    }

    public function test_rbac_001_super_admin_can_access_super_admin_panel(): void
    {
        $this->withSession(['admin_logged_in' => true]);

        $this->get(route('admin.dashboard'))->assertOk();
        $this->get(route('admin.restaurants'))->assertOk();
        $this->get(route('admin.billing'))->assertOk();
    }

    // ── RBAC-002: Owner Access Rules & Deactivation Gate ────────────

    public function test_rbac_002_super_admin_can_access_any_restaurant_dashboard(): void
    {
        $r = $this->createRestaurant('Tenant Kitchen');
        $this->withSession(['admin_logged_in' => true]);

        $this->get(route('dashboard.orders', $r->id))->assertOk();
        $this->get(route('dashboard.settings', $r->id))->assertOk();
    }

    public function test_rbac_002_deactivated_restaurant_owner_is_blocked(): void
    {
        $r = $this->createRestaurant('Suspended Kitchen', active: false);
        $this->withSession(["restaurant_{$r->id}" => true]);

        // Non-superadmin is blocked with 403 when restaurant is inactive
        $response = $this->get(route('dashboard.orders', $r->id));
        $response->assertForbidden();
    }
}
