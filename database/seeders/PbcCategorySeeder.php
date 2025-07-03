<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PbcCategory;

class PbcCategorySeeder extends Seeder
{
    public function run()
    {
        $categories = [
            [
                'name' => '1. Permanent File',
                'code' => 'permanent_file',
                'description' => 'Documents that remain consistent across audit periods including corporate documents, agreements, and legal filings.',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'name' => '2. Current File',
                'code' => 'current_file',
                'description' => 'Documents specific to the current audit period including trial balances, schedules, and period-specific reports.',
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'name' => '3. Tax and Compliance',
                'code' => 'tax_compliance',
                'description' => 'Tax-related documents, compliance filings, and regulatory submissions.',
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'name' => '4. Supporting Documents',
                'code' => 'supporting_documents',
                'description' => 'Additional supporting documentation and confirmations.',
                'sort_order' => 4,
                'is_active' => true,
            ],
            [
                'name' => '5. Management Representations',
                'code' => 'management_representations',
                'description' => 'Management letters, representations, and confirmations.',
                'sort_order' => 5,
                'is_active' => true,
            ],
        ];

        foreach ($categories as $category) {
            PbcCategory::firstOrCreate(
                ['code' => $category['code']],
                $category
            );
        }

        $this->command->info('PBC Categories seeded successfully!');
    }
}
