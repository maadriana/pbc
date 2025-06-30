<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use App\Models\Client;
use App\Models\Project;

class CreateMessageSystem extends Command
{
    protected $signature = 'pbc:create-message-system {--force : Force recreation even if system exists}';
    protected $description = 'Create the complete PBC message system (migrations, models, controllers, etc.)';

    public function handle()
    {
        $this->info('🚀 Creating PBC Message System...');

        // Check if system already exists
        if (!$this->option('force') && $this->systemExists()) {
            $this->info('📋 Message system already exists!');

            if (!$this->confirm('Do you want to continue anyway? This will clear existing data.')) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        // Pre-flight checks
        if (!$this->preFlightChecks()) {
            return 1;
        }

        // Run migrations
        $this->info('📦 Running migrations...');
        try {
            Artisan::call('migrate', ['--force' => true]);
            $this->info('✅ Migrations completed');
        } catch (\Exception $e) {
            $this->error('❌ Migration failed: ' . $e->getMessage());
            return 1;
        }

        // Create storage directories
        $this->createStorageDirectories();

        // Seed message data
        $this->info('🌱 Seeding message data...');
        try {
            Artisan::call('db:seed', ['--class' => 'MessageSeeder', '--force' => true]);
            $this->info('✅ Message data seeded');
        } catch (\Exception $e) {
            $this->warn('⚠️  Seeding failed: ' . $e->getMessage());
            $this->info('💡 This is usually because you need clients and projects first.');

            if ($this->confirm('Would you like to create sample clients and projects?')) {
                $this->createSampleData();

                // Try seeding again
                try {
                    Artisan::call('db:seed', ['--class' => 'MessageSeeder', '--force' => true]);
                    $this->info('✅ Message data seeded successfully');
                } catch (\Exception $e2) {
                    $this->error('❌ Seeding still failed: ' . $e2->getMessage());
                }
            }
        }

        // Clear cache
        $this->info('🧹 Clearing cache...');
        Artisan::call('pbc:clear-message-cache');

        // Post-installation verification
        $this->verifyInstallation();

        $this->info('🎉 PBC Message System created successfully!');
        $this->info('');
        $this->info('🌐 Available API endpoints:');
        $this->info('GET    /api/v1/messages/conversations           - Get user conversations');
        $this->info('POST   /api/v1/messages/conversations           - Create new conversation');
        $this->info('GET    /api/v1/messages/conversations/{id}      - Get conversation details');
        $this->info('GET    /api/v1/messages/conversations/{id}/messages - Get conversation messages');
        $this->info('POST   /api/v1/messages/send                    - Send message');
        $this->info('PUT    /api/v1/messages/messages/{id}/read      - Mark message as read');
        $this->info('GET    /api/v1/messages/unread-count            - Get unread count');
        $this->info('GET    /api/v1/messages/available-users         - Get available users');
        $this->info('');
        $this->info('🖥️  Web interface: /messages');
        $this->info('');
        $this->info('🔧 Troubleshooting commands:');
        $this->info('php artisan pbc:clear-message-cache --fix       - Diagnose and fix issues');
        $this->info('php artisan pbc:clear-message-cache             - Clear message cache');

        return 0;
    }

    protected function systemExists()
    {
        $tables = ['pbc_conversations', 'pbc_messages', 'pbc_conversation_participants'];

        foreach ($tables as $table) {
            if (!Schema::hasTable($table)) {
                return false;
            }
        }

        return true;
    }

    protected function preFlightChecks()
    {
        $this->info('🔍 Running pre-flight checks...');

        // Check database connection
        try {
            \DB::connection()->getPdo();
            $this->info('✅ Database connection: OK');
        } catch (\Exception $e) {
            $this->error('❌ Database connection failed: ' . $e->getMessage());
            return false;
        }

        // Check if User model exists and has required methods
        try {
            $user = new User();
            if (!method_exists($user, 'hasPermission')) {
                $this->warn('⚠️  User model missing hasPermission method');
                $this->info('💡 You may need to add permission checking logic');
            } else {
                $this->info('✅ User model: OK');
            }
        } catch (\Exception $e) {
            $this->error('❌ User model check failed: ' . $e->getMessage());
            return false;
        }

        // Check storage permissions
        $storagePath = storage_path('app/public');
        if (!is_writable($storagePath)) {
            $this->error("❌ Storage path not writable: {$storagePath}");
            $this->info('Please run: chmod -R 755 storage/');
            return false;
        } else {
            $this->info('✅ Storage permissions: OK');
        }

        return true;
    }

    protected function createStorageDirectories()
    {
        $this->info('📁 Creating storage directories...');

        $directories = [
            'conversations',
            'conversations/attachments',
            'conversations/temp'
        ];

        foreach ($directories as $dir) {
            $path = storage_path("app/public/{$dir}");
            if (!is_dir($path)) {
                mkdir($path, 0755, true);
                $this->info("✅ Created: {$dir}");
            } else {
                $this->info("✅ Exists: {$dir}");
            }
        }
    }

    protected function createSampleData()
    {
        $this->info('🏗️  Creating sample data...');

        try {
            // Create sample client
            $client = Client::firstOrCreate(
                ['name' => 'Sample Corp'],
                [
                    'email' => 'contact@samplecorp.com',
                    'phone' => '+63 2 1234 5678',
                    'address' => '123 Business St, Makati City',
                    'contact_person' => 'John Doe',
                    'is_active' => true
                ]
            );

            // Create sample project
            $project = Project::firstOrCreate(
                [
                    'client_id' => $client->id,
                    'engagement_type' => 'audit'
                ],
                [
                    'name' => 'Annual Audit 2024',
                    'engagement_period' => now(),
                    'status' => 'active',
                    'description' => 'Annual financial audit for 2024'
                ]
            );

            $this->info('✅ Sample data created');
            return true;

        } catch (\Exception $e) {
            $this->error('❌ Failed to create sample data: ' . $e->getMessage());
            return false;
        }
    }

    protected function verifyInstallation()
    {
        $this->info('🔍 Verifying installation...');

        $checks = [
            'Tables exist' => $this->systemExists(),
            'Storage writable' => is_writable(storage_path('app/public/conversations')),
            'Users exist' => User::count() > 0,
            'Clients exist' => Client::count() > 0,
            'Projects exist' => Project::count() > 0
        ];

        foreach ($checks as $check => $result) {
            if ($result) {
                $this->info("✅ {$check}");
            } else {
                $this->warn("⚠️  {$check}");
            }
        }

        // Count created records
        try {
            $conversations = \App\Models\PbcConversation::count();
            $messages = \App\Models\PbcMessage::count();

            $this->info('');
            $this->info('📊 Installation summary:');
            $this->info("- Conversations: {$conversations}");
            $this->info("- Messages: {$messages}");
            $this->info("- Users: " . User::count());
            $this->info("- Clients: " . Client::count());
            $this->info("- Projects: " . Project::count());

        } catch (\Exception $e) {
            $this->warn('⚠️  Could not get installation summary: ' . $e->getMessage());
        }
    }
}
