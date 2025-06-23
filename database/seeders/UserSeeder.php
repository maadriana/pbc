<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run()
    {
        $users = [
            [
                'name' => 'System Administrator',
                'email' => 'admin@pbcaudit.com',
                'entity' => 'PBC Audit System',
                'role' => 'system_admin',
                'access_level' => 1,
                'contact_number' => '+63 917 123 4567',
            ],
            [
                'name' => 'John Smith',
                'email' => 'john.smith@auditfirm.com',
                'entity' => 'Smith & Associates CPA',
                'role' => 'engagement_partner',
                'access_level' => 2,
                'contact_number' => '+63 917 234 5678',
            ],
            [
                'name' => 'Maria Santos',
                'email' => 'maria.santos@auditfirm.com',
                'entity' => 'Smith & Associates CPA',
                'role' => 'engagement_partner',
                'access_level' => 2,
                'contact_number' => '+63 917 345 6789',
            ],
            [
                'name' => 'Sarah Johnson',
                'email' => 'sarah.johnson@auditfirm.com',
                'entity' => 'Smith & Associates CPA',
                'role' => 'manager',
                'access_level' => 3,
                'contact_number' => '+63 917 456 7890',
            ],
            [
                'name' => 'Robert Kim',
                'email' => 'robert.kim@auditfirm.com',
                'entity' => 'Smith & Associates CPA',
                'role' => 'manager',
                'access_level' => 3,
                'contact_number' => '+63 917 567 8901',
            ],
            [
                'name' => 'Mike Wilson',
                'email' => 'mike.wilson@auditfirm.com',
                'entity' => 'Smith & Associates CPA',
                'role' => 'associate',
                'access_level' => 4,
                'contact_number' => '+63 917 678 9012',
            ],
            [
                'name' => 'Jane Doe',
                'email' => 'jane.doe@auditfirm.com',
                'entity' => 'Smith & Associates CPA',
                'role' => 'associate',
                'access_level' => 4,
                'contact_number' => '+63 917 789 0123',
            ],
            [
                'name' => 'Alex Brown',
                'email' => 'alex.brown@auditfirm.com',
                'entity' => 'Smith & Associates CPA',
                'role' => 'associate',
                'access_level' => 4,
                'contact_number' => '+63 917 890 1234',
            ],
            [
                'name' => 'Lisa Chen',
                'email' => 'lisa.chen@abccorp.com',
                'entity' => 'ABC Corporation',
                'role' => 'guest',
                'access_level' => 5,
                'contact_number' => '+63 917 901 2345',
            ],
            [
                'name' => 'Carlos Reyes',
                'email' => 'carlos.reyes@xyxltd.com',
                'entity' => 'XYZ Limited',
                'role' => 'guest',
                'access_level' => 5,
                'contact_number' => '+63 917 012 3456',
            ],
        ];

        foreach ($users as $data) {
            User::firstOrCreate(
                ['email' => $data['email']],
                array_merge($data, [
                    'password' => Hash::make('password'),
                    'is_active' => true,
                ])
            );
        }
    }
}
