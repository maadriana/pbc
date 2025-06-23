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
            $data = $this->dashboardService->getDashboardData($request->user());
            return $this->success($data, 'Dashboard data retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve dashboard data', $e->getMessage(), 500);
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
