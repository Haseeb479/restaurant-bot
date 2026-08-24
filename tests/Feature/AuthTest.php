<?php

namespace Tests\Feature;

use App\Models\Restaurant;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Covers the Phase 3 auth hardening:
 *  - no `admin123` default
 *  - owner passwords verified as hashes, with legacy plaintext upgraded on login
 *  - self-registered (already-hashed) owners can log in — the old plaintext
 *    comparison locked them out entirely
 *  - login throttling
 *  - cross-tenant dashboard isolation
 */
class AuthTest extends TestCase
{
    use RefreshDatabase;

    private function makeRestaurant(string $password, bool $hashed = true): Restaurant
    {
        $r = new Restaurant([
            'name'            => 'Test Kitchen',
            'whatsapp_number' => '9232' . random_int(10000000, 99999999),
            'owner_phone'     => '923001234567',
            'is_active'       => true,
            'is_open'         => true,
            'plan'            => 'trial',
        ]);
        // owner_password is guarded, so assign it explicitly.
        $r->owner_password = $hashed ? Hash::make($password) : $password;
        $r->save();

        return $r;
    }

    // ── Super-admin ────────────────────────────────────────────

    public function test_admin_login_rejects_the_removed_admin123_default(): void
    {
        config(['app.admin_password' => null]);
        Setting::query()->delete();

        $this->post('/admin/login', ['password' => 'admin123'])
            ->assertSessionHasErrors('password');

        $this->assertGuestAdmin();
    }

    public function test_admin_login_refuses_everything_when_unconfigured(): void
    {
        config(['app.admin_password' => null]);

        $this->post('/admin/login', ['password' => ''])->assertSessionHasErrors('password');
        $this->post('/admin/login', ['password' => 'anything'])->assertSessionHasErrors('password');

        $this->assertGuestAdmin();
    }

    public function test_admin_login_succeeds_and_persists_a_hash_not_plaintext(): void
    {
        config(['app.admin_password' => 'a-strong-master-password']);

        $this->post('/admin/login', ['password' => 'a-strong-master-password'])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertTrue(session('admin_logged_in'));

        // The plaintext env value must have been migrated into a hash at rest.
        $stored = Setting::get('admin_password_hash');
        $this->assertNotNull($stored);
        $this->assertNotSame('a-strong-master-password', $stored);
        $this->assertTrue(Hash::check('a-strong-master-password', $stored));
    }

    public function test_admin_password_can_be_rotated_from_settings(): void
    {
        config(['app.admin_password' => 'original-master-password']);
        $this->withSession(['admin_logged_in' => true]);

        // Wrong current password is rejected.
        $this->post('/admin/settings', [
            'current_password' => 'not-it',
            'new_password'     => 'brand-new-master-pw',
        ])->assertSessionHasErrors('current_password');

        // Too-short new password is rejected.
        $this->post('/admin/settings', [
            'current_password' => 'original-master-password',
            'new_password'     => 'short',
        ])->assertSessionHasErrors('new_password');

        // Correct rotation works and forces re-auth.
        $this->post('/admin/settings', [
            'current_password' => 'original-master-password',
            'new_password'     => 'brand-new-master-pw',
        ])->assertRedirect(route('admin.login'));

        $this->assertNull(session('admin_logged_in'));
        $this->assertTrue(Hash::check('brand-new-master-pw', Setting::get('admin_password_hash')));

        // The new password works and the old one no longer does.
        $this->post('/admin/login', ['password' => 'original-master-password'])
            ->assertSessionHasErrors('password');
        $this->post('/admin/login', ['password' => 'brand-new-master-pw'])
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_admin_login_is_throttled(): void
    {
        config(['app.admin_password' => 'a-strong-master-password']);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/admin/login', ['password' => 'wrong'])->assertStatus(302);
        }

        $this->post('/admin/login', ['password' => 'wrong'])->assertStatus(429);
    }

    // ── Restaurant owner ──────────────────────────────────────

    public function test_owner_with_hashed_password_can_log_in(): void
    {
        // This is the case the old plaintext comparison broke outright:
        // self-registered owners were stored hashed and could never log in.
        $r = $this->makeRestaurant('owner-secret-password');

        $this->post("/dashboard/{$r->id}/login", ['password' => 'owner-secret-password'])
            ->assertRedirect(route('dashboard.orders', $r->id));

        $this->assertTrue(session("restaurant_{$r->id}"));
    }

    public function test_legacy_plaintext_owner_can_log_in_and_is_upgraded_to_a_hash(): void
    {
        $r = $this->makeRestaurant('legacy-plain-pw', hashed: false);
        $this->assertSame('legacy-plain-pw', $r->fresh()->owner_password);

        $this->post("/dashboard/{$r->id}/login", ['password' => 'legacy-plain-pw'])
            ->assertRedirect(route('dashboard.orders', $r->id));

        $stored = $r->fresh()->owner_password;
        $this->assertNotSame('legacy-plain-pw', $stored, 'password should no longer be plaintext');
        $this->assertTrue(Hash::check('legacy-plain-pw', $stored));

        // Still works on the next login, now via the hash path.
        session()->flush();
        $this->post("/dashboard/{$r->id}/login", ['password' => 'legacy-plain-pw'])
            ->assertRedirect(route('dashboard.orders', $r->id));
    }

    public function test_owner_login_rejects_wrong_password(): void
    {
        $r = $this->makeRestaurant('owner-secret-password');

        $this->post("/dashboard/{$r->id}/login", ['password' => 'nope'])
            ->assertSessionHasErrors('password');

        $this->assertNull(session("restaurant_{$r->id}"));
    }

    public function test_owner_login_rejects_empty_password(): void
    {
        $r = $this->makeRestaurant('owner-secret-password');

        $this->post("/dashboard/{$r->id}/login", ['password' => ''])
            ->assertSessionHasErrors('password');
        $this->assertNull(session("restaurant_{$r->id}"));
    }

    public function test_owner_login_is_throttled(): void
    {
        $r = $this->makeRestaurant('owner-secret-password');

        for ($i = 0; $i < 5; $i++) {
            $this->post("/dashboard/{$r->id}/login", ['password' => 'wrong'])->assertStatus(302);
        }

        $this->post("/dashboard/{$r->id}/login", ['password' => 'wrong'])->assertStatus(429);
    }

    // ── Authorization ─────────────────────────────────────────

    public function test_dashboard_requires_authentication(): void
    {
        $r = $this->makeRestaurant('owner-secret-password');

        $this->get(route('dashboard.orders', $r->id))->assertForbidden();
        $this->get(route('dashboard.settings', $r->id))->assertForbidden();
    }

    public function test_owner_cannot_access_another_restaurants_dashboard(): void
    {
        $mine   = $this->makeRestaurant('owner-secret-password');
        $theirs = $this->makeRestaurant('other-secret-password');

        $this->withSession(["restaurant_{$mine->id}" => true]);

        $this->get(route('dashboard.orders', $mine->id))->assertOk();
        $this->get(route('dashboard.orders', $theirs->id))->assertForbidden();
    }

    public function test_admin_panel_requires_authentication(): void
    {
        $this->get(route('admin.dashboard'))->assertForbidden();
        $this->get(route('admin.restaurants'))->assertForbidden();
        $this->get(route('admin.settings'))->assertForbidden();
    }

    private function assertGuestAdmin(): void
    {
        $this->assertNull(session('admin_logged_in'));
    }
}
