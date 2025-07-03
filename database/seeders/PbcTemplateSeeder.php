<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PbcTemplate;
use App\Models\User;

class PbcTemplateSeeder extends Seeder
{
    public function run()
    {
        // Get system admin user for created_by
        $systemAdmin = User::where('role', 'system_admin')->first();

        if (!$systemAdmin) {
            $this->command->error('No system admin user found. Please run UserSeeder first.');
            return;
        }

        $templates = [
            [
                'name' => 'AT-700',
                'code' => 'at_700',
                'description' => 'Standard audit checklist for annual financial statement audits. Comprehensive list covering permanent file items and current period documentation requirements.',
                'engagement_types' => ['audit', 'accounting'],
                'is_default' => true,
                'is_active' => true,
                'created_by' => $systemAdmin->id,
            ],
            [
                'name' => 'AT-690',
                'code' => 'at_690',
                'description' => 'Alternative audit checklist for smaller entities or specific engagement types.',
                'engagement_types' => ['audit'],
                'is_default' => false,
                'is_active' => true,
                'created_by' => $systemAdmin->id,
            ],
            [
                'name' => 'TAX-100',
                'code' => 'tax_100',
                'description' => 'Tax preparation and compliance checklist for corporate tax returns.',
                'engagement_types' => ['tax'],
                'is_default' => true,
                'is_active' => true,
                'created_by' => $systemAdmin->id,
            ],
            [
                'name' => 'REVIEW-200',
                'code' => 'review_200',
                'description' => 'Review engagement checklist for compilation and review services.',
                'engagement_types' => ['accounting', 'special_engagement'],
                'is_default' => false,
                'is_active' => true,
                'created_by' => $systemAdmin->id,
            ],
            [
                'name' => 'CUSTOM-001',
                'code' => 'custom_001',
                'description' => 'Custom template for special engagements and unique client requirements.',
                'engagement_types' => ['special_engagement', 'others'],
                'is_default' => false,
                'is_active' => true,
                'created_by' => $systemAdmin->id,
            ],
        ];

        foreach ($templates as $template) {
            PbcTemplate::firstOrCreate(
                ['code' => $template['code']],
                $template
            );
        }

        $this->command->info('PBC Templates seeded successfully!');
        $this->command->info('Templates created: ' . count($templates));
    }
}
