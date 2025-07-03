<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PbcRequest;
use App\Models\PbcRequestItem;
use App\Models\PbcTemplate;
use App\Models\Project;
use App\Models\User;
use App\Models\Client;

class SamplePbcRequestSeeder extends Seeder
{
    public function run()
    {
        // Get required data
        $at700Template = PbcTemplate::where('code', 'at_700')->first();
        $projects = Project::with('client')->get();
        $systemAdmin = User::where('role', 'system_admin')->first();
        $clients = Client::all();

        if (!$at700Template || $projects->isEmpty() || !$systemAdmin) {
            $this->command->error('Required data not found. Please run other seeders first.');
            return;
        }

        foreach ($projects as $project) {
            // Get client contact (guest user) if exists
            $clientContact = User::where('email', $project->contact_email)->first();

            // Create a sample PBC request for each project
            $pbcRequest = PbcRequest::create([
                'project_id' => $project->id,
                'template_id' => $at700Template->id,
                'title' => "AT-700 Annual Audit " . $project->engagement_period->format('Y') . " - " . $project->client->name,
                'client_name' => $project->client->name,
                'audit_period' => $project->engagement_period->format('Y-m-d'),
                'contact_person' => $project->contact_person,
                'contact_email' => $project->contact_email,
                'engagement_partner' => $project->engagementPartner?->name,
                'engagement_manager' => $project->manager?->name,
                'document_date' => now(),
                'status' => 'active',
                'completion_percentage' => 0,
                'total_items' => 0,
                'completed_items' => 0,
                'pending_items' => 0,
                'overdue_items' => 0,
                'created_by' => $systemAdmin->id,
                'assigned_to' => $clientContact?->id,
                'due_date' => now()->addDays(30), // 30 days from now
                'notes' => 'Sample PBC request created during seeding process.',
                'client_notes' => 'Please provide all requested documents in digital format (PDF preferred).',
                'status_note' => 'Initial request created. Client has been notified.',
            ]);

            // Create request items from template items
            $this->createRequestItemsFromTemplate($pbcRequest, $at700Template, $systemAdmin, $clientContact);

            // Update progress
            $pbcRequest->updateProgress();

            // Update project PBC progress
            $project->updatePbcProgress();
        }

        $this->command->info('Sample PBC Requests seeded successfully!');
        $this->command->info('PBC Requests created: ' . PbcRequest::count());
        $this->command->info('PBC Request Items created: ' . PbcRequestItem::count());
    }

    private function createRequestItemsFromTemplate($pbcRequest, $template, $systemAdmin, $clientContact)
    {
        $templateItems = $template->templateItems()
            ->with('category')
            ->orderBy('sort_order')
            ->get();

        foreach ($templateItems as $templateItem) {
            $requestItem = PbcRequestItem::create([
                'pbc_request_id' => $pbcRequest->id,
                'template_item_id' => $templateItem->id,
                'category_id' => $templateItem->category_id,
                'parent_id' => null, // We'll handle this later for sub-items
                'item_number' => $templateItem->item_number,
                'sub_item_letter' => $templateItem->sub_item_letter,
                'description' => $templateItem->description,
                'sort_order' => $templateItem->sort_order,
                'status' => 'pending',
                'date_requested' => now(),
                'due_date' => now()->addDays(21), // 3 weeks from now
                'days_outstanding' => 0,
                'requested_by' => $systemAdmin->id,
                'assigned_to' => $clientContact?->id,
                'is_required' => $templateItem->is_required,
                'is_custom' => false,
            ]);

            // Simulate some progress for demonstration
            $this->simulateItemProgress($requestItem, $systemAdmin, $clientContact);
        }

        // Handle parent-child relationships for sub-items
        $this->updateParentChildRelationships($pbcRequest);
    }

    private function updateParentChildRelationships($pbcRequest)
    {
        // Get all items for this request
        $requestItems = $pbcRequest->items()->get();

        // Create a mapping of template items to request items
        $templateToRequestMap = [];
        foreach ($requestItems as $item) {
            if ($item->template_item_id) {
                $templateToRequestMap[$item->template_item_id] = $item->id;
            }
        }

        // Update parent_id for sub-items
        foreach ($requestItems as $item) {
            if ($item->template_item_id) {
                $templateItem = $item->templateItem;
                if ($templateItem && $templateItem->parent_id) {
                    $parentRequestItemId = $templateToRequestMap[$templateItem->parent_id] ?? null;
                    if ($parentRequestItemId) {
                        $item->update(['parent_id' => $parentRequestItemId]);
                    }
                }
            }
        }
    }

    private function simulateItemProgress($requestItem, $systemAdmin, $clientContact)
    {
        // Randomly simulate some items as completed for demonstration
        $random = rand(1, 10);

        if ($random <= 2) {
            // 20% chance - mark as accepted
            $requestItem->update([
                'status' => 'accepted',
                'date_submitted' => now()->subDays(rand(1, 7)),
                'date_reviewed' => now()->subDays(rand(0, 3)),
                'reviewed_by' => $systemAdmin->id,
                'remarks' => 'Document reviewed and accepted. Good quality submission.',
            ]);
        } elseif ($random <= 4) {
            // 20% chance - mark as submitted (under review)
            $requestItem->update([
                'status' => 'submitted',
                'date_submitted' => now()->subDays(rand(1, 5)),
            ]);
        } elseif ($random <= 6) {
            // 20% chance - mark as rejected
            $requestItem->update([
                'status' => 'rejected',
                'date_submitted' => now()->subDays(rand(3, 10)),
                'date_reviewed' => now()->subDays(rand(1, 5)),
                'reviewed_by' => $systemAdmin->id,
                'remarks' => 'Document quality insufficient. Please resubmit with clearer copies.',
            ]);
        } elseif ($random <= 7) {
            // 10% chance - mark as overdue
            $requestItem->update([
                'status' => 'overdue',
                'due_date' => now()->subDays(rand(1, 7)),
                'days_outstanding' => now()->diffInDays($requestItem->date_requested),
            ]);
        }
        // Remaining 30% stay as pending
    }
}
