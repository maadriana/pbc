<?php

namespace App\Services;

use App\Models\PbcReminder;
use App\Models\PbcRequest;
use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PbcReminderService
{
    public function getFilteredReminders(array $filters, User $user): LengthAwarePaginator
    {
        $query = PbcReminder::with(['remindable', 'sender', 'recipient']);

        // Apply access control
        if ($user->isGuest()) {
            $query->where('sent_to', $user->id);
        } elseif (!$user->isSystemAdmin()) {
            $query->where(function($q) use ($user) {
                $q->where('sent_by', $user->id)
                  ->orWhere('sent_to', $user->id);
            });
        }

        // Apply filters
        $query->when($filters['status'] ?? null, function ($query, $status) {
            $query->where('status', $status);
        })
        ->when($filters['type'] ?? null, function ($query, $type) {
            $query->where('type', $type);
        })
        ->when($filters['method'] ?? null, function ($query, $method) {
            $query->where('method', $method);
        })
        ->when($filters['sent_to'] ?? null, function ($query, $sentTo) {
            $query->where('sent_to', $sentTo);
        })
        ->when($filters['is_auto'] ?? null, function ($query, $isAuto) {
            $query->where('is_auto', $isAuto);
        })
        ->when($filters['date_from'] ?? null, function ($query, $dateFrom) {
            $query->where('scheduled_at', '>=', $dateFrom);
        })
        ->when($filters['date_to'] ?? null, function ($query, $dateTo) {
            $query->where('scheduled_at', '<=', $dateTo);
        });

        return $query->orderBy('scheduled_at', 'desc')
                    ->paginate($filters['per_page'] ?? 25);
    }

    public function sendReminder(array $reminderData, User $user): PbcReminder
    {
        DB::beginTransaction();

        try {
            $reminder = PbcReminder::create(array_merge($reminderData, [
                'sent_by' => $user->id,
                'scheduled_at' => $reminderData['scheduled_at'] ?? now(),
                'is_auto' => false,
            ]));

            // Send immediately if scheduled time is now or past
            if ($reminder->scheduled_at->isPast()) {
                $reminder->send();
            }

            // Log activity
            AuditLog::logPbcActivity('reminder_created', $reminder,
                "Reminder created for: {$reminder->subject}", $user);

            DB::commit();
            return $reminder->load(['remindable', 'recipient']);

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function sendBulkReminders(array $pbcRequestIds, string $type, User $user, ?string $customMessage = null): array
    {
        DB::beginTransaction();

        try {
            $pbcRequests = PbcRequest::whereIn('id', $pbcRequestIds)->with('assignedTo')->get();
            $results = ['success' => 0, 'failed' => 0, 'errors' => []];

            foreach ($pbcRequests as $request) {
                try {
                    if (!$request->assignedTo) {
                        $results['failed']++;
                        $results['errors'][] = "No assignee for request: {$request->title}";
                        continue;
                    }

                    $subject = $this->generateReminderSubject($type, $request);
                    $message = $customMessage ?? $this->generateReminderMessage($type, $request);

                    $reminder = PbcReminder::create([
                        'remindable_type' => PbcRequest::class,
                        'remindable_id' => $request->id,
                        'subject' => $subject,
                        'message' => $message,
                        'type' => $type,
                        'method' => 'email',
                        'scheduled_at' => now(),
                        'sent_by' => $user->id,
                        'sent_to' => $request->assignedTo->id,
                        'is_auto' => false,
                    ]);

                    $reminder->send();
                    $results['success']++;

                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = "Failed to send reminder for '{$request->title}': " . $e->getMessage();
                }
            }

            // Log bulk activity
            AuditLog::logPbcActivity('bulk_reminders_sent', null,
                "Bulk reminders sent: {$results['success']} successful, {$results['failed']} failed", $user);

            DB::commit();
            return $results;

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function processDueReminders(): int
    {
        $dueReminders = PbcReminder::dueToSend()->get();
        $processedCount = 0;

        foreach ($dueReminders as $reminder) {
            try {
                $reminder->send();
                $processedCount++;
            } catch (\Exception $e) {
                \Log::error('Failed to send reminder', [
                    'reminder_id' => $reminder->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $processedCount;
    }

    public function createAutoReminders(PbcRequest $pbcRequest): array
    {
        if (!config('pbc.reminders.auto_reminders_enabled', true)) {
            return [];
        }

        if (!$pbcRequest->assignedTo || !$pbcRequest->due_date) {
            return [];
        }

        $schedule = config('pbc.reminders.reminder_schedule', [
            'follow_up' => 3,
            'urgent' => 1,
        ]);

        $createdReminders = [];

        foreach ($schedule as $type => $daysBefore) {
            $scheduledAt = $pbcRequest->due_date->subDays($daysBefore);

            if ($scheduledAt->isFuture()) {
                $reminder = PbcReminder::createAutoReminder(
                    $pbcRequest,
                    $pbcRequest->assignedTo->id,
                    $type,
                    $daysBefore
                );

                if ($reminder) {
                    $createdReminders[] = $reminder;
                }
            }
        }

        return $createdReminders;
    }

    private function generateReminderSubject(string $type, PbcRequest $request): string
    {
        return match($type) {
            'initial' => "New PBC Request: {$request->title}",
            'follow_up' => "PBC Reminder: {$request->title}",
            'urgent' => "Urgent: PBC Due Soon - {$request->title}",
            'final_notice' => "Final Notice: Overdue PBC - {$request->title}",
            default => "PBC Reminder: {$request->title}"
        };
    }

    private function generateReminderMessage(string $type, PbcRequest $request): string
    {
        $daysUntilDue = $request->getDaysUntilDue();
        $urgencyText = match($type) {
            'initial' => 'A new PBC request has been assigned to you.',
            'follow_up' => 'This is a friendly reminder about your pending PBC request.',
            'urgent' => 'Your PBC request is due soon. Please take immediate action.',
            'final_notice' => 'Your PBC request is now overdue. Please submit immediately.',
            default => 'You have a pending PBC request that requires attention.'
        };

        $dueDateText = $request->due_date
            ? "Due Date: {$request->due_date->format('F j, Y')}"
            : "No due date specified";

        if ($daysUntilDue !== null) {
            if ($daysUntilDue > 0) {
                $dueDateText .= " ({$daysUntilDue} days remaining)";
            } elseif ($daysUntilDue < 0) {
                $dueDateText .= " (" . abs($daysUntilDue) . " days overdue)";
            } else {
                $dueDateText .= " (Due today)";
            }
        }

        return "Dear {$request->assignedTo->name},\n\n{$urgencyText}\n\n" .
               "Request: {$request->title}\n" .
               "Client: {$request->client_name}\n" .
               "{$dueDateText}\n\n" .
               "Please log in to the PBC system to review and submit the required documents.\n\n" .
               "Thank you.";
    }

    public function cancelReminder(PbcReminder $reminder, User $user): PbcReminder
    {
        DB::beginTransaction();

        try {
            $reminder->cancel($user->id);

            // Log activity
            AuditLog::logPbcActivity('reminder_cancelled', $reminder,
                "Reminder cancelled: {$reminder->subject}", $user);

            DB::commit();
            return $reminder->fresh();

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function rescheduleReminder(PbcReminder $reminder, $newScheduledAt, User $user): PbcReminder
    {
        DB::beginTransaction();

        try {
            $reminder->reschedule($newScheduledAt);

            // Log activity
            AuditLog::logPbcActivity('reminder_rescheduled', $reminder,
                "Reminder rescheduled to: {$newScheduledAt}", $user);

            DB::commit();
            return $reminder->fresh();

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }
}
