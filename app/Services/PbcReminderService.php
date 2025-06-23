<?php

namespace App\Services;

use App\Models\PbcReminder;
use App\Models\PbcRequest;
use App\Models\User;
use App\Models\AuditLog;
use App\Mail\PbcReminderMail;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Mail;

class PbcReminderService
{
    public function getFilteredReminders(array $filters, User $user): LengthAwarePaginator
    {
        $query = PbcReminder::with(['pbcRequest.project.client', 'sentBy', 'sentTo'])
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('subject', 'like', "%{$search}%")
                      ->orWhere('message', 'like', "%{$search}%");
                });
            })
            ->when($filters['type'] ?? null, function ($query, $type) {
                $query->where('type', $type);
            })
            ->when($filters['is_read'] !== null, function ($query) use ($filters) {
                $query->where('is_read', $filters['is_read']);
            })
            ->when($filters['sent_by'] ?? null, function ($query, $sentBy) {
                $query->where('sent_by', $sentBy);
            })
            ->when($filters['sent_to'] ?? null, function ($query, $sentTo) {
                $query->where('sent_to', $sentTo);
            });

        // Apply user-based filtering
        if (!$user->isSystemAdmin() && !$user->isEngagementPartner()) {
            $query->where(function ($q) use ($user) {
                $q->where('sent_by', $user->id)
                  ->orWhere('sent_to', $user->id);
            });
        }

        $query->orderBy($filters['sort_by'] ?? 'sent_at', $filters['sort_order'] ?? 'desc');

        return $query->paginate($filters['per_page'] ?? 25);
    }

    public function sendReminder(array $data, User $sentBy): PbcReminder
    {
        $pbcRequest = PbcRequest::find($data['pbc_request_id']);
        $sentTo = User::find($data['sent_to']);

        $reminder = PbcReminder::create([
            'pbc_request_id' => $data['pbc_request_id'],
            'sent_by' => $sentBy->id,
            'sent_to' => $data['sent_to'],
            'subject' => $data['subject'],
            'message' => $data['message'],
            'type' => $data['type'],
            'sent_at' => now(),
        ]);

        // Send email notification
        try {
            Mail::to($sentTo->email)->send(new PbcReminderMail($reminder, $pbcRequest));
        } catch (\Exception $e) {
            // Log email failure but don't fail the request
            \Log::error('Failed to send reminder email: ' . $e->getMessage());
        }

        $this->logActivity('reminder_sent', $reminder, $sentBy, 'Reminder sent');

        return $reminder->load(['pbcRequest', 'sentBy', 'sentTo']);
    }

    public function sendBulkReminders(array $pbcRequestIds, string $type, User $sentBy, ?string $customMessage = null): array
    {
        $sent = 0;
        $errors = [];

        foreach ($pbcRequestIds as $pbcRequestId) {
            try {
                $pbcRequest = PbcRequest::with(['assignedTo'])->find($pbcRequestId);

                if (!$pbcRequest || !$pbcRequest->assignedTo) {
                    $errors[] = "PBC request {$pbcRequestId} not found or has no assigned user";
                    continue;
                }

                $subject = $this->generateReminderSubject($type, $pbcRequest);
                $message = $customMessage ?? $this->generateReminderMessage($type, $pbcRequest);

                $this->sendReminder([
                    'pbc_request_id' => $pbcRequestId,
                    'sent_to' => $pbcRequest->assignedTo->id,
                    'subject' => $subject,
                    'message' => $message,
                    'type' => $type,
                ], $sentBy);

                $sent++;
            } catch (\Exception $e) {
                $errors[] = "Failed to send reminder for PBC request {$pbcRequestId}: " . $e->getMessage();
            }
        }

        return [
            'sent' => $sent,
            'errors' => $errors,
            'total' => count($pbcRequestIds)
        ];
    }

    private function generateReminderSubject(string $type, PbcRequest $pbcRequest): string
    {
        $subjects = [
            'follow_up' => "Follow-up: {$pbcRequest->title}",
            'urgent' => "URGENT: {$pbcRequest->title}",
            'final_notice' => "FINAL NOTICE: {$pbcRequest->title}",
        ];

        return $subjects[$type] ?? "Reminder: {$pbcRequest->title}";
    }

    private function generateReminderMessage(string $type, PbcRequest $pbcRequest): string
    {
        $daysUntilDue = $pbcRequest->getDaysUntilDue();
        $isOverdue = $pbcRequest->isOverdue();

        $messages = [
            'follow_up' => $isOverdue
                ? "This PBC request is overdue by {$pbcRequest->getDaysOverdue()} days. Please submit the required documents as soon as possible."
                : "This PBC request is due in {$daysUntilDue} days. Please prepare the required documents.",
            'urgent' => "This PBC request requires immediate attention. Please submit the required documents urgently.",
            'final_notice' => "This is a final notice for the overdue PBC request. Please submit the required documents immediately to avoid delays in the audit process.",
        ];

        return $messages[$type] ?? "Please review and respond to this PBC request.";
    }

    private function logActivity(string $action, PbcReminder $reminder, User $user, string $description): void
    {
        AuditLog::create([
            'user_id' => $user->id,
            'action' => $action,
            'model_type' => PbcReminder::class,
            'model_id' => $reminder->id,
            'description' => $description,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
