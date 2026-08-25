<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Smoke tests for the public entry points — replaces the Laravel scaffold test,
 * which asserted `/` returns 200 even though the app deliberately redirects it.
 */
class SmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_renders_unified_portal_for_guests(): void
    {
        $this->get('/')->assertOk();
    }

    public function test_admin_login_page_renders_for_guests(): void
    {
        $this->get(route('admin.login'))->assertOk();
    }

    public function test_registration_page_renders_for_guests(): void
    {
        $this->get(route('restaurant.register'))->assertOk();
    }

    public function test_tracking_page_renders_without_a_code(): void
    {
        $this->get(route('order.track.live'))->assertOk();
    }

    public function test_tracking_page_handles_an_unknown_code_without_erroring(): void
    {
        $this->get('/track/NOSUCHCODE')->assertOk();
    }
}
