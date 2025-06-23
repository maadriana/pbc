<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Client;

class ClientSeeder extends Seeder
{
    public function run()
    {
        Client::create([
            'name' => 'ABC Corporation',
            'sec_registration_no' => 'SEC123456789',
            'industry_classification' => 'Manufacturing',
            'business_address' => '123 Business Street, Makati City, Metro Manila, Philippines',
            'primary_contact_name' => 'Lisa Chen',
            'primary_contact_email' => 'lisa.chen@abccorp.com',
            'primary_contact_number' => '+63 917 901 2345',
            'secondary_contact_name' => 'Mark Johnson',
            'secondary_contact_email' => 'mark.johnson@abccorp.com',
            'secondary_contact_number' => '+63 917 902 3456',
            'is_active' => true,
        ]);

        Client::create([
            'name' => 'XYZ Limited',
            'sec_registration_no' => 'SEC987654321',
            'industry_classification' => 'Technology',
            'business_address' => '456 Tech Avenue, BGC, Taguig City, Metro Manila, Philippines',
            'primary_contact_name' => 'Carlos Reyes',
            'primary_contact_email' => 'carlos.reyes@xyxltd.com',
            'primary_contact_number' => '+63 917 012 3456',
            'secondary_contact_name' => 'Anna Martinez',
            'secondary_contact_email' => 'anna.martinez@xyxltd.com',
            'secondary_contact_number' => '+63 917 013 4567',
            'is_active' => true,
        ]);

        Client::create([
            'name' => 'DEF Industries Inc.',
            'sec_registration_no' => 'SEC456789123',
            'industry_classification' => 'Construction',
            'business_address' => '789 Industrial Road, Quezon City, Metro Manila, Philippines',
            'primary_contact_name' => 'Roberto Santos',
            'primary_contact_email' => 'roberto.santos@defindustries.com',
            'primary_contact_number' => '+63 917 123 4567',
            'secondary_contact_name' => 'Carmen Lopez',
            'secondary_contact_email' => 'carmen.lopez@defindustries.com',
            'secondary_contact_number' => '+63 917 124 5678',
            'is_active' => true,
        ]);
    }
}
