<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;

class DashboardController extends BaseController
{
    protected $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    public function index(Request $request)
    {
        try {
            // For web routes, return blade view
            if ($request->expectsJson()) {
                $data = $this->dashboardService->getDashboardData($request->user());
                return $this->success($data, 'Dashboard data retrieved successfully');
            }

            // For blade view
            $data = $this->dashboardService->getDashboardData($request->user());

            return view('dashboard', [
                'stats' => $data['statistics'],
                'recent_activity' => $data['recent_activity'],
                'pending_requests' => $data['pending_requests'],
                'overdue_requests' => $data['overdue_requests'],
                'upcoming_deadlines' => $data['upcoming_deadlines'],
                'charts_data' => $data['charts_data'],
            ]);
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return $this->error('Failed to retrieve dashboard data', $e->getMessage(), 500);
            }

            // For blade view, redirect with error
            return redirect()->back()->with('error', 'Failed to load dashboard data');
        }
    }

    public function stats(Request $request)
    {
        try {
            $stats = $this->dashboardService->getStatistics($request->user());
            return $this->success($stats, 'Statistics retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve statistics', $e->getMessage(), 500);
        }
    }

    public function recentActivity(Request $request)
    {
        try {
            $activities = $this->dashboardService->getRecentActivity($request->user());
            return $this->success($activities, 'Recent activity retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve recent activity', $e->getMessage(), 500);
        }
    }
}
