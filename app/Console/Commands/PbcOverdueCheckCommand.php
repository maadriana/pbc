<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PbcRequest;
use App\Services\PbcReminderService;
use Carbon\Carbon;

class PbcOverdueCheckCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'pbc:check-overdue {--dry-run : Show what would be updated without making changes}';

    /**
     * The console command description.
     */
    protected $description = 'Check for overdue PBC requests and update their status';

    protected $reminderService;

    public function __construct(PbcReminderService $reminderService)
    {
        parent::__construct();
        $this->reminderService = $reminderService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for overdue PBC requests...');

        $dryRun = $this->option('dry-run');

        // Find pending requests that are past due date
        $overdueRequests = PbcRequest::where('status', 'pending')
            ->where('due_date', '<', Carbon::today())
            ->with(['assignedTo', 'project.client'])
            ->get();

        if ($overdueRequests->isEmpty()) {
            $this->info('No overdue requests found.');
            return;
        }

        $this->warn("Found {$overdueRequests->count()} overdue requests:");

        foreach ($overdueRequests as $request) {
            $daysOverdue = Carbon::today()->diffInDays($request->due_date);
            $clientName = $request->project->client->name ?? 'Unknown Client';

            $this->line("- {$request->title} ({$clientName}) - {$daysOverdue} days overdue");

            if (!$dryRun) {
                // Update status to overdue
                $request->update(['status' => 'overdue']);

                // Send overdue notification if enabled
                if (config('pbc.reminders.auto_enabled') && $request->assignedTo) {
                    try {
                        $this->reminderService->sendReminder([
                            'pbc_request_id' => $request->id,
                            'sent_to' => $request->assignedTo->id,
                            'subject' => "OVERDUE: {$request->title}",
                            'message' => "This PBC request is overdue by {$daysOverdue} days. Please submit the required documents immediately.",
                            'type' => 'urgent',
                        ], $request->requestor);

                        $this->info("  ✓ Notification sent to {$request->assignedTo->name}");
                    } catch (\Exception $e) {
                        $this->error("  ✗ Failed to send notification: {$e->getMessage()}");
                    }
                }
            }
        }

        if ($dryRun) {
            $this->info("\nDry run completed. Use without --dry-run to make actual changes.");
        } else {
            $this->info("\nOverdue check completed. Updated {$overdueRequests->count()} requests.");
        }
    }
}
