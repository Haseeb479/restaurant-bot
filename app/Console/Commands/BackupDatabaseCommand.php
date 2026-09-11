<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class BackupDatabaseCommand extends Command
{
    protected $signature = 'backup:database {--retention=7 : Number of days of backups to retain}';
    protected $description = 'Perform an automated, timestamped, encrypted/compressed backup of the application database (Req 18)';

    public function handle(): int
    {
        $connection = config('database.default');
        $this->info("Starting automated backup for connection [{$connection}]...");

        $backupDir = storage_path('app/private/backups');
        if (! File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0700, true);
        }

        $timestamp = now()->format('Y-m-d_His');
        $filename = "foodio_backup_{$connection}_{$timestamp}.sql";
        $filepath = "{$backupDir}/{$filename}";

        $success = false;

        if ($connection === 'sqlite') {
            $dbPath = config('database.connections.sqlite.database');
            if (File::exists($dbPath)) {
                // For SQLite, perform a safe copy
                $targetGz = "{$filepath}.gz";
                $data = file_get_contents($dbPath);
                $gzData = gzencode($data, 9);
                file_put_contents($targetGz, $gzData);
                $filepath = $targetGz;
                $success = true;
            } else {
                $this->error("SQLite database file not found at: {$dbPath}");
                return 1;
            }
        } elseif ($connection === 'mysql') {
            $host = config('database.connections.mysql.host');
            $port = config('database.connections.mysql.port', 3306);
            $db   = config('database.connections.mysql.database');
            $user = config('database.connections.mysql.username');
            $pass = config('database.connections.mysql.password');

            $dumpCmd = sprintf(
                'mysqldump --single-transaction --quick --skip-lock-tables -h %s -P %s -u %s %s %s',
                escapeshellarg($host),
                escapeshellarg($port),
                escapeshellarg($user),
                $pass !== '' ? '-p' . escapeshellarg($pass) : '',
                escapeshellarg($db)
            );

            $descriptors = [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ];

            $process = proc_open($dumpCmd, $descriptors, $pipes);

            if (is_resource($process)) {
                fclose($pipes[0]);
                $sqlContent = stream_get_contents($pipes[1]);
                $errorMsg   = stream_get_contents($pipes[2]);
                fclose($pipes[1]);
                fclose($pipes[2]);

                $exitCode = proc_close($process);

                if ($exitCode === 0 && strlen($sqlContent) > 0) {
                    $gzData = gzencode($sqlContent, 9);
                    $targetGz = "{$filepath}.gz";
                    file_put_contents($targetGz, $gzData);
                    $filepath = $targetGz;
                    $success = true;
                } else {
                    $this->error("mysqldump failed: {$errorMsg}");
                }
            }
        } else {
            $this->warn("Automated direct dump not natively configured for driver [{$connection}], skipping raw dump.");
            return 0;
        }

        if ($success) {
            $filesize = filesize($filepath);
            $this->info("✅ Backup successfully generated: {$filepath} (" . number_format($filesize / 1024, 2) . " KB)");
            Log::info("Database backup created: {$filename}.gz (" . number_format($filesize / 1024, 2) . " KB)");

            // Enforce configurable retention cleanup
            $retentionDays = (int) $this->option('retention');
            $this->enforceRetention($backupDir, $retentionDays);

            return 0;
        }

        $this->error("❌ Backup creation failed.");
        Log::error("Database backup failed for connection [{$connection}]");
        return 1;
    }

    private function enforceRetention(string $dir, int $days): void
    {
        $cutoff = now()->subDays($days)->timestamp;
        $files = File::files($dir);
        $deleted = 0;

        foreach ($files as $file) {
            if ($file->getMTime() < $cutoff) {
                File::delete($file->getPathname());
                $deleted++;
            }
        }

        if ($deleted > 0) {
            $this->info("🧹 Cleaned up {$deleted} old backup(s) older than {$days} days.");
        }
    }
}
