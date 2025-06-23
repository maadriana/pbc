<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendReminderRequest;
use App\Models\PbcReminder;
use App\Services\PbcReminderService;
use Illuminate\Http\Request;

class PbcReminderController extends BaseController
{
    protected $pbcReminderService;

    public function __construct(PbcReminderService $pbcReminderService)
    {
        $this->pbcReminderService = $pbcReminderService;
    }

    public function index(Request $request)
    {
        try {
            $reminders = $this->pbcReminderService->getFilteredReminders($request->all(), $request->user());
            return $this->paginated($reminders, 'Reminders retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve reminders', $e->getMessage(), 500);
        }
    }

    public function store(SendReminderRequest $request)
    {
        try {
            $reminder = $this->pbcReminderService->sendReminder($request->validated(), $request->user());
            return $this->success($reminder, 'Reminder sent successfully', 201);
        } catch (\Exception $e) {
            return $this->error('Failed to send reminder', $e->getMessage(), 500);
        }
    }

    public function markAsRead(PbcReminder $reminder)
    {
        try {
            $this->authorize('read_reminder', $reminder);

            $reminder->markAsRead();
            return $this->success(null, 'Reminder marked as read');
        } catch (\Exception $e) {
            return $this->error('Failed to mark reminder as read', $e->getMessage(), 500);
        }
    }

    public function bulkSend(Request $request)
    {
        try {
            $this->authorize('send_reminder');

            $request->validate([
                'pbc_request_ids' => 'required|array',
                'pbc_request_ids.*' => 'exists:pbc_requests,id',
                'type' => 'required|in:follow_up,urgent,final_notice',
                'custom_message' => 'nullable|string|max:1000'
            ]);

            $result = $this->pbcReminderService->sendBulkReminders(
                $request->pbc_request_ids,
                $request->type,
                $request->user(),
                $request->custom_message
            );

            return $this->success($result, 'Bulk reminders sent successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to send bulk reminders', $e->getMessage(), 500);
        }
    }
}
