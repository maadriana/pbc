<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Illuminate\Http\Request;

class ReportController extends BaseController
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function pbcStatus(Request $request)
    {
        try {
            $this->authorize('export_reports');

            $request->validate([
                'project_id' => 'nullable|exists:projects,id',
                'status' => 'nullable|in:pending,completed,overdue',
                'format' => 'required|in:pdf,excel,csv',
                'date_from' => 'nullable|date',
                'date_to' => 'nullable|date|after_or_equal:date_from',
            ]);

            $report = $this->reportService->generatePbcStatusReport($request->all(), $request->user());

            return response()->download($report['file_path'], $report['filename'])
                ->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            return $this->error('Failed to generate PBC status report', $e->getMessage(), 500);
        }
    }

    public function projectProgress(Request $request)
    {
        try {
            $this->authorize('export_reports');

            $request->validate([
                'project_id' => 'required|exists:projects,id',
                'format' => 'required|in:pdf,excel',
            ]);

            $report = $this->reportService->generateProjectProgressReport($request->project_id, $request->format);

            return response()->download($report['file_path'], $report['filename'])
                ->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            return $this->error('Failed to generate project progress report', $e->getMessage(), 500);
        }
    }

    public function auditTrail(Request $request)
    {
        try {
            $this->authorize('view_audit_log');

            $request->validate([
                'user_id' => 'nullable|exists:users,id',
                'action' => 'nullable|string',
                'date_from' => 'nullable|date',
                'date_to' => 'nullable|date|after_or_equal:date_from',
                'format' => 'required|in:pdf,excel,csv',
            ]);

            $report = $this->reportService->generateAuditTrailReport($request->all());

            return response()->download($report['file_path'], $report['filename'])
                ->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            return $this->error('Failed to generate audit trail report', $e->getMessage(), 500);
        }
    }
}
