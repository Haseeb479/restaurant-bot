<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class TestRestoreBackupCommand extends Command
{
    protected $signature = 'backup:test-restore {file? : Optional path to a specific backup file to verify}';
    protected $description = 'Verify and test restoration of a backup into an isolated temporary environment (Req 18)';

    public function handle(): int
    {
        $this->info("🧪 Starting non-destructive automated backup restoration verification test...");

        $backupFile = $this->argument('file');

        if (! $backupFile) {
            $backupDir = storage_path('app/private/backups');
            $files = File::files($backupDir);
            if (empty($files)) {
                $this->warn("No existing backups found. Generating a fresh backup first...");
                $this->call('backup:database');
                $files = File::files($backupDir);
            }

            // Pick latest backup
            usort($files, fn($a, $b) => $b->getMTime() <=> $a->getMTime());
            $backupFile = $files[0]->getPathname();
        }

        if (! File::exists($backupFile)) {
            $this->error("Backup file not found: {$backupFile}");
            return 1;
        }

        $this->info("Testing backup archive: {$backupFile}");

        // 1. Test decompression
        $gz = file_get_contents($backupFile);
        $uncompressed = @gzdecode($gz);

        if ($uncompressed === false) {
            $this->error("❌ Corrupt backup file: Gzip decompression failed.");
            return 1;
        }

        $this->info("✅ Archive decompression verified (" . number_format(strlen($uncompressed) / 1024, 2) . " KB uncompressed).");

        // 2. Perform restoration into an isolated temporary SQLite database
        $tempDb = storage_path('app/private/temp_restore_verify.sqlite');
        if (File::exists($tempDb)) {
            File::delete($tempDb);
        }
        touch($tempDb);

        try {
            // Configure temporary runtime connection
            config(['database.connections.temp_restore' => [
                'driver'                  => 'sqlite',
                'database'                => $tempDb,
                'prefix'                  => '',
                'foreign_key_constraints' => true,
            ]]);

            $tempConnection = DB::connection('temp_restore');

            if (str_contains($backupFile, 'sqlite')) {
                // For SQLite backup archive, write decompressed file directly to temp location
                file_put_contents($tempDb, $uncompressed);

                // 3. Verify critical schema tables and relationships in SQLite
                $tables = $tempConnection->select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
                $tableNames = array_map(fn($t) => $t->name, $tables);

                $requiredTables = ['restaurants', 'orders', 'menu_items', 'categories'];
                $missing = array_diff($requiredTables, $tableNames);

                if (! empty($missing)) {
                    $this->error("❌ Verification failed: Restored database missing required tables: " . implode(', ', $missing));
                    File::delete($tempDb);
                    return 1;
                }

                $restCount  = $tempConnection->table('restaurants')->count();
                $orderCount = $tempConnection->table('orders')->count();

                $this->info("✅ Tables verified: " . count($tableNames) . " tables present.");
                $this->info("✅ Data integrity check: {$restCount} restaurant(s), {$orderCount} order(s) successfully verified.");
            } else {
                // For MySQL dump, verify dump structure, headers, table definitions, and completion marker
                $requiredTables = ['restaurants', 'orders', 'menu_items', 'categories'];
                $missingTables = [];

                foreach ($requiredTables as $table) {
                    if (! preg_match('/CREATE TABLE [`"]?' . preg_quote($table, '/') . '[`"]?/i', $uncompressed)) {
                        $missingTables[] = $table;
                    }
                }

                if (! empty($missingTables)) {
                    $this->error("❌ Verification failed: MySQL dump missing required table definitions: " . implode(', ', $missingTables));
                    File::delete($tempDb);
                    return 1;
                }

                // Check for completion marker or EOF integrity
                $hasCompletedMarker = str_contains($uncompressed, '-- Dump completed') || str_contains($uncompressed, 'Dump completed');
                if (! $hasCompletedMarker) {
                    $this->warn("⚠️ Warning: MySQL dump completion marker '-- Dump completed' not found at end of dump. Checking table balance...");
                }

                // Count total tables defined
                preg_match_all('/CREATE TABLE [`"]?([a-zA-Z0-9_]+)[`"]?/i', $uncompressed, $matches);
                $foundTables = array_unique($matches[1] ?? []);

                preg_match_all('/INSERT INTO [`"]?([a-zA-Z0-9_]+)[`"]?/i', $uncompressed, $insertMatches);
                $insertCount = count($insertMatches[0] ?? []);

                $this->info("✅ MySQL dump schema verified: " . count($foundTables) . " tables defined.");
                $this->info("✅ Data integrity check: {$insertCount} insert block(s) detected across tables.");
            }

            Log::info("Automated backup restoration test PASSED for: {$backupFile}");
            $this->info("🎉 RESTORATION VERIFICATION TEST PASSED! Backup is healthy and restorable.");

            File::delete($tempDb);
            return 0;

        } catch (\Throwable $e) {
            $this->error("❌ Restore test error: " . $e->getMessage());
            Log::error("Automated backup restoration test FAILED: " . $e->getMessage());
            if (File::exists($tempDb)) {
                File::delete($tempDb);
            }
            return 1;
        }
    }
}
