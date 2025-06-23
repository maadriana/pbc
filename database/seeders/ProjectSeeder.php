<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Project;
use App\Models\User;
use App\Models\Client;

class ProjectSeeder extends Seeder
{
    public function run()
    {
        $clients = Client::all();
        $engagementPartner = User::where('role', 'engagement_partner')->first();
        $manager = User::where('role', 'manager')->first();
        $associates = User::where('role', 'associate')->take(2)->get();

        Project::create([
            'client_id' => $clients[0]->id,
            'engagement_type' => 'audit',
            'engagement_period' => '2024-12-31',
            'contact_person' => 'Lisa Chen',
            'contact_email' => 'lisa.chen@abccorp.com',
            'contact_number' => '+63 917 901 2345',
            'engagement_partner_id' => $engagementPartner->id,
            'manager_id' => $manager->id,
            'associate_1_id' => $associates[0]->id,
            'associate_2_id' => $associates[1]->id,
            'status' => 'active',
            'progress_percentage' => 65.50,
            'notes' => 'Annual audit for year ending December 31, 2024',
        ]);

        Project::create([
            'client_id' => $clients[1]->id,
            'engagement_type' => 'tax',
            'engagement_period' => '2024-12-31',
            'contact_person' => 'Carlos Reyes',
            'contact_email' => 'carlos.reyes@xyxltd.com',
            'contact_number' => '+63 917 012 3456',
            'engagement_partner_id' => $engagementPartner->id,
            'manager_id' => $manager->id,
            'associate_1_id' => $associates[0]->id,
            'associate_2_id' => $associates[1]->id,
            'status' => 'active',
            'progress_percentage' => 40.25,
            'notes' => 'Tax compliance and advisory services',
        ]);

        Project::create([
            'client_id' => $clients[2]->id,
            'engagement_type' => 'audit',
            'engagement_period' => '2024-12-31',
            'contact_person' => 'Roberto Santos',
            'contact_email' => 'roberto.santos@defindustries.com',
            'contact_number' => '+63 917 123 4567',
            'engagement_partner_id' => $engagementPartner->id,
            'manager_id' => $manager->id,
            'associate_1_id' => $associates[0]->id,
            'associate_2_id' => $associates[1]->id,
            'status' => 'active',
            'progress_percentage' => 20.75,
            'notes' => 'First-time audit engagement',
        ]);
    }
}
