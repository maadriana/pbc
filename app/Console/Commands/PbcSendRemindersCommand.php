<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PbcRequest;
use App\Services\PbcReminderService;
use Carbon\Carbon;

class PbcSendRemindersCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'pbc:send-reminders
                            {--days=3 : Number of days before due date to send reminders}
                            {--type=follow_up : Type of reminder to send}
                            {--dry-run : Show what reminders would be sent without sending them}';

    /**
     * The console command description.
     */
    protected $description = 'Send reminder notifications for pending PBC requests';

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
        // FIX: Convert string to integer
        $days = (int) $this->option('days');
        $type = $this->option('type');
        $dryRun = $this->option('dry-run');

        $this->info("Checking for PBC requests due in {$days} days...");

        // Find requests that are due soon and haven't received a reminder today
        $upcomingRequests = PbcRequest::where('status', 'pending')
            ->where('due_date', '<=', Carbon::today()->addDays($days))
            ->where('due_date', '>=', Carbon::today())
            ->whereDoesntHave('reminders', function ($query) use ($type) {
                $query->where('type', $type)
                      ->where('sent_at', '>=', Carbon::today());
            })
            ->with(['assignedTo', 'requestor', 'project.client'])
            ->get();

        if ($upcomingRequests->isEmpty()) {
            $this->info('No pending requests requiring reminders.');
            return;
        }

        $this->warn("Found {$upcomingRequests->count()} requests requiring reminders:");

        $sent = 0;
        $errors = 0;

        foreach ($upcomingRequests as $request) {
            $daysUntilDue = Carbon::today()->diffInDays($request->due_date, false);
            $clientName = $request->project->client->name ?? 'Unknown Client';

            if (!$request->assignedTo) {
                $this->error("- {$request->title} ({$clientName}) - No assigned user");
                continue;
            }

            $this->line("- {$request->title} ({$clientName}) - Due in {$daysUntilDue} days - Assigned to {$request->assignedTo->name}");

            if (!$dryRun) {
                try {
                    $subject = $this->generateSubject($type, $request, $daysUntilDue);
                    $message = $this->generateMessage($type, $request, $daysUntilDue);

                    $this->reminderService->sendReminder([
                        'pbc_request_id' => $request->id,
                        'sent_to' => $request->assignedTo->id,
                        'subject' => $subject,
                        'message' => $message,
                        'type' => $type,
                    ], $request->requestor);

                    $this->info("  ✓ Reminder sent");
                    $sent++;
                } catch (\Exception $e) {
                    $this->error("  ✗ Failed to send reminder: {$e->getMessage()}");
                    $errors++;
                }
            }
        }

        if ($dryRun) {
            $this->info("\nDry run completed. Use without --dry-run to send actual reminders.");
        } else {
            $this->info("\nReminder process completed. Sent: {$sent}, Errors: {$errors}");
        }
    }

    private function generateSubject(string $type, PbcRequest $request, int $daysUntilDue): string
    {
        switch ($type) {
            case 'urgent':
                return "URGENT: {$request->title} - Due in {$daysUntilDue} days";
            case 'final_notice':
                return "FINAL NOTICE: {$request->title}";
            default:
                return "Reminder: {$request->title} - Due in {$daysUntilDue} days";
        }
    }

    private function generateMessage(string $type, PbcRequest $request, int $daysUntilDue): string
    {
        $clientName = $request->project->client->name ?? 'the client';

        switch ($type) {
            case 'urgent':
                return "This PBC request for {$clientName} requires urgent attention. Please submit the required documents within {$daysUntilDue} days to avoid delays in the audit process.";
            case 'final_notice':
                return "This is a final notice for the PBC request for {$clientName}. Please submit the required documents immediately to avoid project delays.";
            default:
                return "This is a friendly reminder that the PBC request for {$clientName} is due in {$daysUntilDue} days. Please prepare and submit the required documents.";
        }
    }
}
