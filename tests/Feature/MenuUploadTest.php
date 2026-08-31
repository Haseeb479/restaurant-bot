<?php

namespace Tests\Feature;

use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Covers the Phase 3 upload hardening (C-03).
 *
 * The original code took the extension straight from the client filename and
 * moved the file into `public/uploads/menus`, so an authenticated owner could
 * upload `evil.php` and then request it over HTTP — remote code execution.
 *
 * IMPORTANT: these tests share `public/uploads/menus` with real uploaded data,
 * and the in-memory test database reuses low primary keys (the first restaurant
 * created here is id 1, the same as a real tenant). Cleanup therefore diffs
 * against a snapshot taken in setUp() and only ever removes files this test
 * created — never a glob on `menu_{id}_*`, which would delete live files.
 */
class MenuUploadTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A valid 188-byte JPEG. Used instead of UploadedFile::fake()->image(),
     * which requires the GD extension (not installed in this environment).
     */
    private const JPEG_BYTES = '/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRof'
        . 'Hh0aHBwcJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPDs0NDL/wAALCAABAAEBAREA/8QAFAABAQAAAAAAAAAAAAAAAAAA'
        . 'AAv/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAwDAQACEQMRAD8AmgA//9k=';

    private string $menusDir;

    /** @var list<string> Files that already existed before this test ran. */
    private array $preExisting = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->menusDir = public_path('uploads/menus');
        if (! is_dir($this->menusDir)) {
            mkdir($this->menusDir, 0755, true);
        }

        $this->preExisting = $this->allFiles();
    }

    protected function tearDown(): void
    {
        // Only remove what this test added. Runs even when a test fails.
        foreach ($this->newFiles() as $name) {
            @unlink($this->menusDir . DIRECTORY_SEPARATOR . $name);
        }

        parent::tearDown();
    }

    /** @return list<string> */
    private function allFiles(): array
    {
        return array_values(array_map('basename', glob($this->menusDir . DIRECTORY_SEPARATOR . '*') ?: []));
    }

    /** @return list<string> Files created since setUp(). */
    private function newFiles(): array
    {
        return array_values(array_diff($this->allFiles(), $this->preExisting));
    }

    private function jpeg(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, base64_decode(self::JPEG_BYTES, true));
    }

    private function restaurant(string $name = 'Upload Test Kitchen'): Restaurant
    {
        $r = new Restaurant([
            'name'            => $name,
            'whatsapp_number' => '9232' . random_int(10000000, 99999999),
            'owner_phone'     => '923001234567',
            'is_active'       => true,
            'is_open'         => true,
            'plan'            => 'trial',
        ]);
        $r->owner_password = Hash::make('owner-secret-password');
        $r->save();

        return $r;
    }

    private function owner(): Restaurant
    {
        $r = $this->restaurant();
        $this->withSession(["restaurant_{$r->id}" => true]);

        return $r;
    }

    // ── The RCE case ──────────────────────────────────────────

    public function test_php_upload_is_rejected_and_never_written_to_disk(): void
    {
        $r = $this->owner();

        $this->post(route('dashboard.upload-menu-file', $r->id), [
            'menu_file' => UploadedFile::fake()->createWithContent('evil.php', '<?php echo "pwned"; ?>'),
        ])->assertSessionHasErrors('menu_file');

        $this->assertSame([], $this->newFiles(), 'no file should have been written');
        $this->assertNull($r->fresh()->menu_file);
    }

    public function test_php_upload_is_rejected_on_the_csv_endpoint_too(): void
    {
        $r = $this->owner();

        $this->post(route('dashboard.upload-menu-csv', $r->id), [
            'csv_file' => UploadedFile::fake()->createWithContent('evil.php', '<?php echo "pwned"; ?>'),
        ])->assertSessionHasErrors('csv_file');

        $this->assertSame([], $this->newFiles());
    }

    public function test_double_extension_upload_cannot_produce_an_executable_file(): void
    {
        $r = $this->owner();

        // Real JPEG content, but the name smuggles `.php` before the allowed suffix.
        $this->post(route('dashboard.upload-menu-file', $r->id), [
            'menu_file' => $this->jpeg('evil.php.jpg'),
        ]);

        $new = $this->newFiles();
        $this->assertCount(1, $new, 'the image content is legitimate, so it is accepted');

        // The saved name must be server-generated: no `.php`, no attacker string.
        $this->assertStringEndsWith('.jpg', $new[0]);
        $this->assertStringNotContainsString('.php', $new[0]);
        $this->assertStringNotContainsString('evil', $new[0]);
    }

    public function test_svg_and_html_uploads_are_rejected(): void
    {
        $r = $this->owner();

        foreach ([
            ['payload.svg',  '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'],
            ['payload.html', '<html><script>alert(1)</script></html>'],
            ['payload.phtml', '<?php system($_GET["c"]); ?>'],
            ['payload.htaccess', 'AddType application/x-httpd-php .jpg'],
        ] as [$name, $content]) {
            $this->post(route('dashboard.upload-menu-file', $r->id), [
                'menu_file' => UploadedFile::fake()->createWithContent($name, $content),
            ])->assertSessionHasErrors('menu_file', "‹{$name}› should have been rejected");
        }

        $this->assertSame([], $this->newFiles());
    }

    // ── The happy path still works ────────────────────────────

    public function test_a_legitimate_image_upload_is_accepted_and_renamed(): void
    {
        $r = $this->owner();

        $this->post(route('dashboard.upload-menu-file', $r->id), [
            'menu_file' => $this->jpeg('My Menu Flyer.jpg'),
        ])->assertRedirect();

        $fresh = $r->fresh();
        $this->assertSame('image', $fresh->menu_file_type);

        // Original name is kept for display only, never used on disk.
        $this->assertSame('My Menu Flyer.jpg', $fresh->menu_file_name);
        $this->assertMatchesRegularExpression(
            "#^uploads/menus/menu_{$r->id}_[0-9a-f]{16}\.jpg$#",
            (string) $fresh->menu_file
        );
        $this->assertFileExists(public_path((string) $fresh->menu_file));
    }

    public function test_a_legitimate_csv_upload_imports_menu_items(): void
    {
        $r = $this->owner();

        $csv = "name,price,category,description\nZinger Burger,350,Burgers,Crispy fillet\nPepsi,80,Drinks,Ice cold\n";

        $this->post(route('dashboard.upload-menu-csv', $r->id), [
            'csv_file' => UploadedFile::fake()->createWithContent('menu.csv', $csv),
        ])->assertRedirect();

        $fresh = $r->fresh();
        $this->assertSame('excel', $fresh->menu_file_type);
        $this->assertMatchesRegularExpression(
            "#^uploads/menus/menu_{$r->id}_[0-9a-f]{16}\.csv$#",
            (string) $fresh->menu_file
        );
        $this->assertGreaterThan(0, $fresh->menuItems()->count(), 'rows should have been imported');
    }

    public function test_uploading_a_replacement_deletes_the_previous_file(): void
    {
        $r = $this->owner();

        $this->post(route('dashboard.upload-menu-file', $r->id), ['menu_file' => $this->jpeg('first.jpg')]);
        $first = (string) $r->fresh()->menu_file;
        $this->assertCount(1, $this->newFiles());

        $this->post(route('dashboard.upload-menu-file', $r->id), ['menu_file' => $this->jpeg('second.jpg')]);
        $second = (string) $r->fresh()->menu_file;

        $this->assertNotSame($first, $second);
        // Stale files make the bot serve an old menu, and they accumulate forever.
        $this->assertCount(1, $this->newFiles(), 'the superseded upload should be removed');
        $this->assertFileDoesNotExist(public_path($first));
        $this->assertFileExists(public_path($second));
    }

    // ── Authorization ─────────────────────────────────────────

    public function test_upload_requires_authentication(): void
    {
        $r = $this->restaurant('Unauthed Kitchen');

        $this->post(route('dashboard.upload-menu-file', $r->id), [
            'menu_file' => $this->jpeg('menu.jpg'),
        ])->assertRedirect();

        $this->assertSame([], $this->newFiles());
        $this->assertNull($r->fresh()->menu_file);
    }

    public function test_owner_cannot_upload_to_another_restaurant(): void
    {
        $this->owner();
        $theirs = $this->restaurant('Other Kitchen');

        $this->post(route('dashboard.upload-menu-file', $theirs->id), [
            'menu_file' => $this->jpeg('menu.jpg'),
        ])->assertRedirect();

        $this->assertSame([], $this->newFiles());
        $this->assertNull($theirs->fresh()->menu_file);
    }

    public function test_csv_with_section_banners_and_metadata_extracts_correct_categories_and_items(): void
    {
        $r = $this->owner();

        // Exact structure of the user's uploaded menu CSV
        $csv = ",,,,,,,\n" .
               ",RESTAURANT MENU,,,,,,\n" .
               ",Good Food • Good Mood | 100% Fresh & Tasty,,,,,,\n" .
               ",,,,,,,\n" .
               ",Category,Item Name,Price (₹),,Category,Item Count,Avg Price (₹)\n" .
               ",──  STARTERS  ──,,,,Starters,5,₹140.00\n" .
               ",Starters,Veg Spring Roll,₹90.00,,Rolls & Wraps,4,₹107.50\n" .
               ",Starters,Chicken Spring Roll,₹120.00,,Main Course,7,₹181.43\n" .
               ",──  ROLLS & WRAPS  ──,,,,,\n" .
               ",Rolls & Wraps,Veg Roll,₹80.00,,,,\n" .
               ",Rolls & Wraps,Chicken Roll,₹120.00,,,,\n" .
               ",Total Items,4,,,,,\n" .
               ",Average Item Price,₹102.50,,,,,\n";

        $this->post(route('dashboard.upload-menu-csv', $r->id), [
            'csv_file' => UploadedFile::fake()->createWithContent('complex_menu.csv', $csv),
        ])->assertRedirect();

        $fresh = $r->fresh();
        $categories = $fresh->categories()->with('items')->get();

        $this->assertCount(2, $categories, 'Should create Starters and Rolls & Wraps categories');
        $this->assertSame(['Starters', 'Rolls & Wraps'], $categories->pluck('name')->values()->toArray());

        $starters = $categories->firstWhere('name', 'Starters');
        $this->assertCount(2, $starters->items);
        $this->assertTrue($starters->items->contains('name', 'Veg Spring Roll'));
        $this->assertSame(90.0, (float)$starters->items->firstWhere('name', 'Veg Spring Roll')->price);

        // Metadata rows must NOT be added as items
        $this->assertFalse($fresh->menuItems()->where('name', 'like', '%Total%')->exists());
        $this->assertFalse($fresh->menuItems()->where('name', 'like', '%Average%')->exists());
        $this->assertFalse($fresh->menuItems()->where('name', 'like', '%──%')->exists());
    }

    public function test_replace_menu_option_clears_old_items(): void
    {
        $r = $this->owner();

        // Create an old category and item
        $oldCat = $r->categories()->create(['name' => 'Old Category']);
        $r->menuItems()->create([
            'category_id' => $oldCat->id,
            'name'        => 'Old Burger',
            'price'       => 100,
        ]);

        $this->assertCount(1, $r->menuItems);

        $newCsv = "Category,Item Name,Price\nPizzas,Fajita Pizza,800\n";

        $this->post(route('dashboard.upload-menu-csv', $r->id), [
            'csv_file'     => UploadedFile::fake()->createWithContent('new.csv', $newCsv),
            'replace_menu' => '1',
        ])->assertRedirect();

        $fresh = $r->fresh();
        $this->assertCount(1, $fresh->menuItems);
        $this->assertSame('Fajita Pizza', $fresh->menuItems->first()->name);
        $this->assertFalse($fresh->menuItems()->where('name', 'Old Burger')->exists());
    }

    public function test_update_item_endpoint_modifies_food_item(): void
    {
        $r = $this->owner();
        $cat = $r->categories()->create(['name' => 'Burgers']);
        $item = $r->menuItems()->create([
            'category_id' => $cat->id,
            'name'        => 'Classic Burger',
            'price'       => 300,
        ]);

        $this->post("/dashboard/{$r->id}/menu/item/{$item->id}/update", [
            'name'        => 'Mega Classic Burger',
            'category_id' => $cat->id,
            'price'       => 450,
            'description' => 'Updated juicy beef patty',
        ])->assertRedirect();

        $item->refresh();
        $this->assertSame('Mega Classic Burger', $item->name);
        $this->assertSame(450.0, (float)$item->price);
        $this->assertSame('Updated juicy beef patty', $item->description);
    }

    public function test_clear_menu_endpoint_deletes_all_items(): void
    {
        $r = $this->owner();
        $cat = $r->categories()->create(['name' => 'Burgers']);
        $r->menuItems()->create([
            'category_id' => $cat->id,
            'name'        => 'Classic Burger',
            'price'       => 300,
        ]);

        $this->post(route('dashboard.clear-menu', $r->id))->assertRedirect();

        $fresh = $r->fresh();
        $this->assertCount(0, $fresh->menuItems);
        $this->assertCount(0, $fresh->categories);
    }
}

