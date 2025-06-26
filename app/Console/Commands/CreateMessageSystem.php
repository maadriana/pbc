<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class CreateMessageSystem extends Command
{
    protected $signature = 'pbc:create-message-system';
    protected $description = 'Create the complete PBC message system (migrations, models, controllers, etc.)';

    public function handle()
    {
        $this->info('🚀 Creating PBC Message System...');

        // Run migrations
        $this->info('📦 Running migrations...');
        Artisan::call('migrate');
        $this->info('✅ Migrations completed');

        // Seed message data
        $this->info('🌱 Seeding message data...');
        Artisan::call('db:seed', ['--class' => 'MessageSeeder']);
        $this->info('✅ Message data seeded');

        $this->info('🎉 PBC Message System created successfully!');
        $this->info('');
        $this->info('Available API endpoints:');
        $this->info('GET    /api/v1/messages/conversations');
        $this->info('POST   /api/v1/messages/conversations');
        $this->info('GET    /api/v1/messages/conversations/{id}/messages');
        $this->info('POST   /api/v1/messages/send');
        $this->info('PUT    /api/v1/messages/messages/{id}/read');
    }
}
