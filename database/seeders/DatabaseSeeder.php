<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🌱 Starting database seeding...');

        // Core system data
        $this->command->info('📥 Seeding core system data...');
        $this->call([
            UserSeeder::class,
            UserPermissionSeeder::class,
            ClientSeeder::class,
            ProjectSeeder::class,
        ]);

        // PBC system data
        $this->command->info('📋 Seeding PBC system data...');
        $this->call([
            PbcCategorySeeder::class,
            PbcTemplateSeeder::class,
            At700TemplateItemsSeeder::class,
            PbcSubItemSeeder::class,
        ]);

        // Sample PBC requests and test data
        $this->command->info('🧪 Seeding sample PBC data...');
        $this->call([
            SamplePbcRequestSeeder::class,
        ]);

        $this->command->info('✅ Database seeding completed successfully!');
        $this->command->info('');
        $this->command->info('📊 Summary:');
        $this->command->info('- Users: ' . \App\Models\User::count());
        $this->command->info('- Clients: ' . \App\Models\Client::count());
        $this->command->info('- Projects: ' . \App\Models\Project::count());
        $this->command->info('- PBC Categories: ' . \App\Models\PbcCategory::count());
        $this->command->info('- PBC Templates: ' . \App\Models\PbcTemplate::count());
        $this->command->info('- PBC Template Items: ' . \App\Models\PbcTemplateItem::count());

        if (class_exists('\App\Models\PbcRequest')) {
            $this->command->info('- Sample PBC Requests: ' . \App\Models\PbcRequest::count());
        }
    }
}
