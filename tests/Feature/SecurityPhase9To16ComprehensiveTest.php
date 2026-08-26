<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Restaurant;
use App\Support\WebhookUrlValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SecurityPhase9To16ComprehensiveTest extends TestCase
{
    use RefreshDatabase;

    private function createActiveRestaurant(string $name = 'Hardened Kitchen'): Restaurant
    {
        $r = new Restaurant([
            'name'                => $name,
            'whatsapp_number'     => '92300' . random_int(1000000, 9999999),
            'owner_phone'         => '92300' . random_int(1000000, 9999999),
            'status'              => 'active',
            'registration_status' => 'approved',
            'is_open'             => true,
            'plan'                => 'trial',
        ]);
        $r->is_active = true;
        $r->owner_password = Hash::make('Secret123');
        $r->save();

        return $r;
    }

    // ── SEC-001: HTTP Security Headers ───────────────────────────────

    public function test_sec_001_responses_include_hardening_security_headers(): void
    {
        $response = $this->get('/login');
        $response->assertOk();

        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    // ── SSRF-001: Webhook URL Validation & Private IP Block ─────────

    public function test_ssrf_001_blocks_private_ips_and_metadata_services(): void
    {
        $this->assertNotNull(WebhookUrlValidator::validate('http://127.0.0.1:3000/restart'));
        $this->assertNotNull(WebhookUrlValidator::validate('http://localhost:8000/admin'));
        $this->assertNotNull(WebhookUrlValidator::validate('http://169.254.169.254/latest/meta-data/'));
        $this->assertNotNull(WebhookUrlValidator::validate('http://192.168.1.1/webhook'));
        $this->assertNotNull(WebhookUrlValidator::validate('http://10.0.0.1/status'));

        // Public HTTPS Google Sheet Webhook is accepted
        $this->assertNull(WebhookUrlValidator::validate('https://script.google.com/macros/s/AKfycbz12345/exec'));
    }

    // ── FILE-001: Malicious Executable Upload Prevention ─────────────

    public function test_file_001_rejects_executable_php_or_script_uploads(): void
    {
        $r = $this->createActiveRestaurant('Upload Secure Kitchen');
        $this->withSession(["restaurant_{$r->id}" => true]);

        // Malicious PHP file pretending to be menu
        $evilPhp = UploadedFile::fake()->create('evil.php', 10, 'application/x-php');

        $response = $this->post(route('dashboard.upload-menu-file', $r->id), [
            'menu_file' => $evilPhp,
        ]);

        $response->assertSessionHasErrors('menu_file');
        $this->assertNull($r->fresh()->menu_file);
    }

    // ── AUDIT-001: Sensitive Action Audit Logging ───────────────────

    public function test_audit_001_logs_sensitive_actions(): void
    {
        AuditLog::log('security.test_event', 'Audit event description test');

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'security.test_event',
        ]);
    }
}
