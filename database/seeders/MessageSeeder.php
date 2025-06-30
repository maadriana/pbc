<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PbcConversation;
use App\Models\PbcMessage;
use App\Models\User;
use App\Models\Client;
use App\Models\Project;

class MessageSeeder extends Seeder
{
    public function run()
    {
        $this->command->info('🌱 Seeding message data...');

        // Get some users and projects
        $systemAdmin = User::where('role', 'system_admin')->first();
        $manager = User::where('role', 'manager')->first();
        $associate = User::where('role', 'associate')->first();
        $guest = User::where('role', 'guest')->first();

        $clients = Client::take(3)->get();
        $projects = Project::take(3)->get();

        if ($clients->isEmpty() || $projects->isEmpty()) {
            $this->command->warn('⚠️  No clients or projects found. Please seed clients and projects first.');
            return;
        }

        if (!$manager || !$associate || !$guest) {
            $this->command->warn('⚠️  Missing required user roles. Please seed users first.');
            return;
        }

        // Create conversations
        $conversations = [];

        // Conversation 1: Active conversation
        $conversation1 = PbcConversation::create([
            'client_id' => $clients[0]->id,
            'project_id' => $projects[0]->id,
            'title' => $clients[0]->name . ' - Annual Audit 2024',
            'status' => 'active',
            'created_by' => $manager->id,
            'last_message_at' => now()->subMinutes(30)
        ]);

        // Add participants
        $conversation1->participants()->attach([
            $manager->id => [
                'joined_at' => now()->subDays(5),
                'role' => 'moderator',
                'is_active' => true
            ],
            $associate->id => [
                'joined_at' => now()->subDays(5),
                'role' => 'participant',
                'is_active' => true
            ],
            $guest->id => [
                'joined_at' => now()->subDays(5),
                'role' => 'participant',
                'is_active' => true
            ]
        ]);

        $conversations[] = $conversation1;

        // Conversation 2: Another active conversation
        $conversation2 = PbcConversation::create([
            'client_id' => $clients[1]->id,
            'project_id' => $projects[1]->id,
            'title' => $clients[1]->name . ' - Tax Compliance 2024',
            'status' => 'active',
            'created_by' => $manager->id,
            'last_message_at' => now()->subHours(2)
        ]);

        $conversation2->participants()->attach([
            $manager->id => [
                'joined_at' => now()->subDays(3),
                'role' => 'moderator',
                'is_active' => true
            ],
            $guest->id => [
                'joined_at' => now()->subDays(3),
                'role' => 'participant',
                'is_active' => true
            ]
        ]);

        $conversations[] = $conversation2;

        // Conversation 3: Completed conversation (only if system admin exists)
        if ($systemAdmin) {
            $conversation3 = PbcConversation::create([
                'client_id' => $clients[2]->id,
                'project_id' => $projects[2]->id,
                'title' => $clients[2]->name . ' - Special Engagement',
                'status' => 'completed',
                'created_by' => $systemAdmin->id,
                'last_message_at' => now()->subDays(1)
            ]);

            $conversation3->participants()->attach([
                $systemAdmin->id => [
                    'joined_at' => now()->subWeek(),
                    'role' => 'moderator',
                    'is_active' => true
                ],
                $manager->id => [
                    'joined_at' => now()->subWeek(),
                    'role' => 'participant',
                    'is_active' => true
                ],
                $guest->id => [
                    'joined_at' => now()->subWeek(),
                    'role' => 'participant',
                    'is_active' => true
                ]
            ]);

            $conversations[] = $conversation3;
            $this->seedMessagesForConversation3($conversation3, $systemAdmin, $manager, $guest);
        }

        // Create messages for each conversation
        $this->seedMessagesForConversation($conversation1, $manager, $associate, $guest);
        $this->seedMessagesForConversation2($conversation2, $manager, $guest);

        $this->command->info('✅ Message data seeded successfully!');
        $this->command->info("Created " . count($conversations) . " conversations with sample messages");
    }

    private function seedMessagesForConversation($conversation, $manager, $associate, $guest)
    {
        // System message - USE MANAGER ID instead of null
        PbcMessage::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $manager->id,
            'message' => "Conversation created by {$manager->name}",
            'message_type' => 'system',
            'is_read' => false,
            'created_at' => now()->subDays(5)
        ]);

        // Client message
        PbcMessage::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $guest->id,
            'message' => 'Good morning! I have uploaded the bank statements for December 2024. Please let me know if you need anything else.',
            'message_type' => 'text',
            'attachments' => [
                [
                    'id' => 'att_' . uniqid(),
                    'name' => 'Bank_Statement_Dec2024.pdf',
                    'filename' => 'bank_stmt_' . uniqid() . '.pdf',
                    'size' => 2458624,
                    'type' => 'pdf',
                    'mime_type' => 'application/pdf',
                    'uploaded_at' => now()->subDays(5)->toISOString()
                ]
            ],
            'is_read' => true,
            'read_at' => now()->subDays(5)->addMinutes(30),
            'created_at' => now()->subDays(5)->addHours(1)
        ]);

        // Manager response
        PbcMessage::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $manager->id,
            'message' => 'Thank you for uploading the bank statements. I have reviewed them and they look complete. I\'m assigning ' . $associate->name . ' to handle the reconciliation review.',
            'message_type' => 'text',
            'is_read' => true,
            'read_at' => now()->subDays(4)->addHours(2),
            'created_at' => now()->subDays(4)->addMinutes(30)
        ]);

        // Associate message
        PbcMessage::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $associate->id,
            'message' => 'I\'ve reviewed the bank statements. I notice there might be some reconciling items. Could you please provide the bank reconciliation as well?',
            'message_type' => 'text',
            'is_read' => false,
            'created_at' => now()->subMinutes(60)
        ]);

        // Recent client response
        PbcMessage::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $guest->id,
            'message' => 'Of course! I\'ll upload the bank reconciliation shortly. Is there a specific format you prefer?',
            'message_type' => 'text',
            'is_read' => false,
            'created_at' => now()->subMinutes(30)
        ]);
    }

    private function seedMessagesForConversation2($conversation, $manager, $guest)
    {
        // System message - USE MANAGER ID instead of null
        PbcMessage::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $manager->id,
            'message' => "Conversation created by {$manager->name}",
            'message_type' => 'system',
            'is_read' => false,
            'created_at' => now()->subDays(3)
        ]);

        PbcMessage::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $manager->id,
            'message' => 'Hello! We need the AR aging report for the tax compliance review. Can you provide this by end of week?',
            'message_type' => 'text',
            'is_read' => true,
            'read_at' => now()->subDays(2),
            'created_at' => now()->subDays(3)->addHours(1)
        ]);

        PbcMessage::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $guest->id,
            'message' => 'Hi! Yes, I can provide that. Let me check our system and I\'ll have it ready by Thursday.',
            'message_type' => 'text',
            'is_read' => true,
            'read_at' => now()->subDays(2)->addHours(1),
            'created_at' => now()->subDays(2)->addMinutes(30)
        ]);

        PbcMessage::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $manager->id,
            'message' => 'Perfect! Thank you for the quick response.',
            'message_type' => 'text',
            'is_read' => false,
            'created_at' => now()->subHours(2)
        ]);
    }

    private function seedMessagesForConversation3($conversation, $systemAdmin, $manager, $guest)
    {
        // System message - USE SYSTEM ADMIN ID instead of null
        PbcMessage::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $systemAdmin->id,
            'message' => "Conversation created by {$systemAdmin->name}",
            'message_type' => 'system',
            'is_read' => false,
            'created_at' => now()->subWeek()
        ]);

        PbcMessage::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $systemAdmin->id,
            'message' => 'This special engagement has been completed successfully. All required documents have been received and reviewed.',
            'message_type' => 'text',
            'is_read' => true,
            'read_at' => now()->subDays(2),
            'created_at' => now()->subDays(2)
        ]);

        PbcMessage::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $guest->id,
            'message' => 'Thank you for the confirmation. We appreciate your thorough work on this engagement.',
            'message_type' => 'text',
            'is_read' => true,
            'read_at' => now()->subDays(1)->addHours(2),
            'created_at' => now()->subDays(1)
        ]);
    }
}
