<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PbcRequest;
use App\Models\Project;
use App\Models\PbcCategory;
use App\Models\User;
use Carbon\Carbon;

class SamplePbcRequestSeeder extends Seeder
{
    public function run()
    {
        $projects = Project::all();
        $categories = PbcCategory::all()->keyBy('code');
        $auditors = User::whereIn('role', ['manager', 'associate'])->get();
        $clients = User::where('role', 'guest')->get();

        if ($projects->count() < 3 || $clients->count() < 2 || $auditors->count() < 2 || !$categories->has('INV')) {
            $this->command->warn('⚠️ Skipping SamplePbcRequestSeeder: insufficient data in projects, clients, auditors, or categories.');
            return;
        }

        $safe = fn($user) => optional($user)->id;

        $sampleRequests = [
    [
        'project_id' => $projects[0]->id,
        'category_id' => $categories['CASH']->id ?? null,
        'title' => 'Bank Statements for 2024',
        'description' => 'Please provide monthly bank statements for all bank accounts for the year ending December 31, 2024.',
        'requestor_id' => 6, // Mike Wilson (associate)
        'assigned_to_id' => 9, // Lisa Chen (client)
        'date_requested' => Carbon::now()->subDays(10),
        'due_date' => Carbon::now()->addDays(5),
        'status' => 'pending',
        'priority' => 'high',
        'notes' => 'Include all checking, savings, and time deposit accounts.',
    ],
    [
        'project_id' => $projects[0]->id,
        'category_id' => $categories['AR']->id ?? null,
        'title' => 'Accounts Receivable Aging Report',
        'description' => 'Please provide detailed AR aging report as of December 31, 2024.',
        'requestor_id' => 7, // Jane Doe (associate)
        'assigned_to_id' => 9, // Lisa Chen (client)
        'date_requested' => Carbon::now()->subDays(8),
        'due_date' => Carbon::now()->addDays(2),
        'status' => 'completed',
        'priority' => 'medium',
        'notes' => 'Received and reviewed.',
        'completed_at' => Carbon::now()->subDays(1),
        'approved_by' => 4, // Sarah Johnson (manager)
        'approved_at' => Carbon::now()->subDays(1),
    ],
    [
        'project_id' => $projects[0]->id,
        'category_id' => $categories['GL']->id ?? null,
        'title' => 'Trial Balance with Notes',
        'description' => 'Please provide detailed trial balance as of December 31, 2024 with explanatory notes.',
        'requestor_id' => 4, // Sarah Johnson (manager)
        'assigned_to_id' => 9, // Lisa Chen (client)
        'date_requested' => Carbon::now()->subDays(25),
        'due_date' => Carbon::now()->subDays(5),
        'status' => 'overdue',
        'priority' => 'urgent',
        'notes' => 'Awaiting TB file with supporting schedules.',
    ],
    [
        'project_id' => $projects[1]->id,
        'category_id' => $categories['TAX']->id ?? null,
        'title' => 'BIR Tax Filings Summary',
        'description' => 'Please provide summary of all BIR tax filings for 2024 including ITR, VAT returns, and withholding tax returns.',
        'requestor_id' => 6, // Mike Wilson (associate)
        'assigned_to_id' => 10, // Carlos Reyes (client)
        'date_requested' => Carbon::now()->subDays(15),
        'due_date' => Carbon::now()->addDays(10),
        'status' => 'pending',
        'priority' => 'medium',
        'notes' => 'Please confirm format requirements.',
    ],
    [
        'project_id' => $projects[1]->id,
        'category_id' => $categories['PAYROLL']->id ?? null,
        'title' => 'Payroll Register and Tax Filings',
        'description' => 'Please provide monthly payroll registers and related government filings for 2024.',
        'requestor_id' => 5, // Robert Kim (manager)
        'assigned_to_id' => 10, // Carlos Reyes (client)
        'date_requested' => Carbon::now()->subDays(12),
        'due_date' => Carbon::now()->addDays(8),
        'status' => 'in_progress',
        'priority' => 'medium',
        'notes' => 'Client is preparing the documents.',
    ],
    [
        'project_id' => $projects[2]->id,
        'category_id' => $categories['INV']->id ?? null,
        'title' => 'Physical Inventory Count Results',
        'description' => 'Please provide physical inventory count sheets and final inventory listing as of December 31, 2024.',
        'requestor_id' => 8, // Alex Brown (associate)
        'assigned_to_id' => 9, // Lisa Chen (client)
        'date_requested' => Carbon::now()->subDays(20),
        'due_date' => Carbon::now()->addDays(15),
        'status' => 'pending',
        'priority' => 'high',
        'notes' => 'Coordinate with warehouse team for count observation.',
    ],
];
        foreach ($sampleRequests as $request) {
            PbcRequest::create($request);
        }
    }
}
