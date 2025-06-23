<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AuditLog;
use Carbon\Carbon;

class PbcCleanupLogsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'pbc:cleanup-logs
                            {--days=2555 : Number of days to retain logs (default: 7 years)}
                            {--dry-run : Show what would be deleted without deleting}
                            {--force : Force deletion without confirmation}';

    /**
     * The console command description.
     */
    protected $description = 'Clean up old audit logs and system logs';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $retentionDays = $this->option('days');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        $cutoffDate = Carbon::now()->subDays($retentionDays);

        $this->info("Checking for audit logs older than {$retentionDays} days (before {$cutoffDate->format('Y-m-d')})...");

        // Count old logs
        $oldLogsCount = AuditLog::where('created_at', '<', $cutoffDate)->count();

        if ($oldLogsCount === 0) {
            $this->info('No old logs found to clean up.');
            return;
        }

        $this->warn("Found {$oldLogsCount} old audit log records.");

        if (!$dryRun && !$force) {
            if (!$this->confirm("Are you sure you want to delete {$oldLogsCount} audit log records?")) {
                $this->info('Cleanup cancelled.');
                return;
            }
        }

        if ($dryRun) {
            $this->info("Dry run: Would delete {$oldLogsCount} audit log records.");

            // Show breakdown by action
            $breakdown = AuditLog::where('created_at', '<', $cutoffDate)
                ->selectRaw('action, COUNT(*) as count')
                ->groupBy('action')
                ->orderBy('count', 'desc')
                ->get();

            $this->table(['Action', 'Count'], $breakdown->map(function ($item) {
                return [$item->action, $item->count];
            })->toArray());
        } else {
            // Perform cleanup in chunks to avoid memory issues
            $deleted = 0;
            $chunkSize = 1000;

            $this->output->progressStart($oldLogsCount);

            AuditLog::where('created_at', '<', $cutoffDate)
                ->chunkById($chunkSize, function ($logs) use (&$deleted) {
                    foreach ($logs as $log) {
                        $log->delete();
                        $deleted++;
                        $this->output->progressAdvance();
                    }
                });

            $this->output->progressFinish();
            $this->info("\nCleanup completed. Deleted {$deleted} audit log records.");
        }

        // Clean up log files as well
        $this->cleanupLogFiles($retentionDays, $dryRun);
    }

    private function cleanupLogFiles(int $retentionDays, bool $dryRun): void
    {
        $logPath = storage_path('logs');
        $cutoffDate = Carbon::now()->subDays($retentionDays);

        if (!is_dir($logPath)) {
            return;
        }

        $oldFiles = [];
        $files = glob($logPath . '/*.log');

        foreach ($files as $file) {
            $fileTime = Carbon::createFromTimestamp(filemtime($file));
            if ($fileTime->lt($cutoffDate)) {
                $oldFiles[] = $file;
            }
        }

        if (empty($oldFiles)) {
            $this->info('No old log files found.');
            return;
        }

        $this->info("Found " . count($oldFiles) . " old log files.");

        if ($dryRun) {
            $this->info("Dry run: Would delete the following log files:");
            foreach ($oldFiles as $file) {
                $this->line("  - " . basename($file));
            }
        } else {
            foreach ($oldFiles as $file) {
                if (unlink($file)) {
                    $this->info("Deleted log file: " . basename($file));
                } else {
                    $this->error("Failed to delete: " . basename($file));
                }
            }
        }
    }
}
