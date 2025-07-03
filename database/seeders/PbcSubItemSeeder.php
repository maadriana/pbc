<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PbcTemplate;
use App\Models\PbcTemplateItem;
use App\Models\PbcCategory;

class PbcSubItemSeeder extends Seeder
{
    public function run()
    {
        // Get the AT-700 template and categories
        $at700Template = PbcTemplate::where('code', 'at_700')->first();
        $permanentFileCategory = PbcCategory::where('code', 'permanent_file')->first();
        $currentFileCategory = PbcCategory::where('code', 'current_file')->first();

        if (!$at700Template || !$permanentFileCategory || !$currentFileCategory) {
            $this->command->error('Required templates or categories not found.');
            return;
        }

        // Create sub-items for Item 6 (Contracts)
        $this->createContractSubItems($at700Template->id, $permanentFileCategory->id);

        // Create sub-items for Item 2 (Schedule of accounts)
        $this->createScheduleSubItems($at700Template->id, $currentFileCategory->id);

        $this->command->info('PBC Sub-items seeded successfully!');
    }

    private function createContractSubItems($templateId, $categoryId)
    {
        // Find parent item
        $contractsItem = PbcTemplateItem::where('template_id', $templateId)
            ->where('item_number', '6')
            ->where('category_id', $categoryId)
            ->first();

        if (!$contractsItem) {
            $this->command->warn('Contracts parent item not found');
            return;
        }

        $subItems = [
            [
                'template_id' => $templateId,
                'category_id' => $categoryId,
                'parent_id' => $contractsItem->id,
                'item_number' => null,
                'sub_item_letter' => 'a',
                'description' => 'Construction contracts',
                'sort_order' => 7,
                'is_required' => false,
                'is_active' => true,
            ],
            [
                'template_id' => $templateId,
                'category_id' => $categoryId,
                'parent_id' => $contractsItem->id,
                'item_number' => null,
                'sub_item_letter' => 'b',
                'description' => 'Loan Agreements',
                'sort_order' => 8,
                'is_required' => false,
                'is_active' => true,
            ],
            [
                'template_id' => $templateId,
                'category_id' => $categoryId,
                'parent_id' => $contractsItem->id,
                'item_number' => null,
                'sub_item_letter' => 'c',
                'description' => 'Lease Agreements',
                'sort_order' => 9,
                'is_required' => false,
                'is_active' => true,
            ],
            [
                'template_id' => $templateId,
                'category_id' => $categoryId,
                'parent_id' => $contractsItem->id,
                'item_number' => null,
                'sub_item_letter' => 'd',
                'description' => 'Others (specify)',
                'sort_order' => 10,
                'is_required' => false,
                'is_active' => true,
            ],
        ];

        foreach ($subItems as $subItem) {
            PbcTemplateItem::firstOrCreate(
                [
                    'template_id' => $subItem['template_id'],
                    'parent_id' => $subItem['parent_id'],
                    'sub_item_letter' => $subItem['sub_item_letter']
                ],
                $subItem
            );
        }

        $this->command->info('Contract sub-items created: ' . count($subItems));
    }

    private function createScheduleSubItems($templateId, $categoryId)
    {
        // Find parent item
        $schedulesItem = PbcTemplateItem::where('template_id', $templateId)
            ->where('item_number', '2')
            ->where('category_id', $categoryId)
            ->first();

        if (!$schedulesItem) {
            $this->command->warn('Schedules parent item not found');
            return;
        }

        $scheduleSubItems = [
            [
                'template_id' => $templateId,
                'category_id' => $categoryId,
                'parent_id' => $schedulesItem->id,
                'item_number' => null,
                'sub_item_letter' => 'a',
                'description' => 'Bank reconciliation schedule',
                'sort_order' => 22,
                'is_required' => true,
                'is_active' => true,
            ],
            [
                'template_id' => $templateId,
                'category_id' => $categoryId,
                'parent_id' => $schedulesItem->id,
                'item_number' => null,
                'sub_item_letter' => 'b',
                'description' => 'Aging of receivables',
                'sort_order' => 23,
                'is_required' => true,
                'is_active' => true,
            ],
            [
                'template_id' => $templateId,
                'category_id' => $categoryId,
                'parent_id' => $schedulesItem->id,
                'item_number' => null,
                'sub_item_letter' => 'c',
                'description' => 'Schedule of Prepayments',
                'sort_order' => 24,
                'is_required' => true,
                'is_active' => true,
            ],
            [
                'template_id' => $templateId,
                'category_id' => $categoryId,
                'parent_id' => $schedulesItem->id,
                'item_number' => null,
                'sub_item_letter' => 'd',
                'description' => 'Schedule of Prepaid Taxes and Licenses',
                'sort_order' => 25,
                'is_required' => true,
                'is_active' => true,
            ],
            [
                'template_id' => $templateId,
                'category_id' => $categoryId,
                'parent_id' => $schedulesItem->id,
                'item_number' => null,
                'sub_item_letter' => 'e',
                'description' => 'Roll forward analysis of property cost and accumulated depreciation',
                'sort_order' => 26,
                'is_required' => true,
                'is_active' => true,
            ],
            [
                'template_id' => $templateId,
                'category_id' => $categoryId,
                'parent_id' => $schedulesItem->id,
                'item_number' => null,
                'sub_item_letter' => 'f',
                'description' => 'Lapsing schedule',
                'sort_order' => 27,
                'is_required' => true,
                'is_active' => true,
            ],
            [
                'template_id' => $templateId,
                'category_id' => $categoryId,
                'parent_id' => $schedulesItem->id,
                'item_number' => null,
                'sub_item_letter' => 'g',
                'description' => 'Schedule of property additions, including construction in progress',
                'sort_order' => 28,
                'is_required' => true,
                'is_active' => true,
            ],
            [
                'template_id' => $templateId,
                'category_id' => $categoryId,
                'parent_id' => $schedulesItem->id,
                'item_number' => null,
                'sub_item_letter' => 'h',
                'description' => 'Schedule of property sales/dispositions, if there\'s any',
                'sort_order' => 29,
                'is_required' => false,
                'is_active' => true,
            ],
            [
                'template_id' => $templateId,
                'category_id' => $categoryId,
                'parent_id' => $schedulesItem->id,
                'item_number' => null,
                'sub_item_letter' => 'i',
                'description' => 'Schedule of security and utility deposits',
                'sort_order' => 30,
                'is_required' => true,
                'is_active' => true,
            ],
            [
                'template_id' => $templateId,
                'category_id' => $categoryId,
                'parent_id' => $schedulesItem->id,
                'item_number' => null,
                'sub_item_letter' => 'j',
                'description' => 'Schedule of trade and other payables',
                'sort_order' => 31,
                'is_required' => true,
                'is_active' => true,
            ],
            [
                'template_id' => $templateId,
                'category_id' => $categoryId,
                'parent_id' => $schedulesItem->id,
                'item_number' => null,
                'sub_item_letter' => 'k',
                'description' => 'Schedule of related party transactions and balance',
                'sort_order' => 32,
                'is_required' => true,
                'is_active' => true,
            ],
            [
                'template_id' => $templateId,
                'category_id' => $categoryId,
                'parent_id' => $schedulesItem->id,
                'item_number' => null,
                'sub_item_letter' => 'l',
                'description' => 'Loan amortization schedule',
                'sort_order' => 33,
                'is_required' => true,
                'is_active' => true,
            ],
            [
                'template_id' => $templateId,
                'category_id' => $categoryId,
                'parent_id' => $schedulesItem->id,
                'item_number' => null,
                'sub_item_letter' => 'm',
                'description' => 'Schedule of operating expenses - Professional fees and consultation fees',
                'sort_order' => 34,
                'is_required' => true,
                'is_active' => true,
            ],
            [
                'template_id' => $templateId,
                'category_id' => $categoryId,
                'parent_id' => $schedulesItem->id,
                'item_number' => null,
                'sub_item_letter' => 'n',
                'description' => 'Schedule of other income and expenses',
                'sort_order' => 35,
                'is_required' => true,
                'is_active' => true,
            ],
            [
                'template_id' => $templateId,
                'category_id' => $categoryId,
                'parent_id' => $schedulesItem->id,
                'item_number' => null,
                'sub_item_letter' => 'o',
                'description' => 'Schedule of accrued expenses and provisions',
                'sort_order' => 36,
                'is_required' => true,
                'is_active' => true,
            ],
            [
                'template_id' => $templateId,
                'category_id' => $categoryId,
                'parent_id' => $schedulesItem->id,
                'item_number' => null,
                'sub_item_letter' => 'p',
                'description' => 'Schedule of contingent liabilities',
                'sort_order' => 37,
                'is_required' => false,
                'is_active' => true,
            ],
        ];

        foreach ($scheduleSubItems as $subItem) {
            PbcTemplateItem::firstOrCreate(
                [
                    'template_id' => $subItem['template_id'],
                    'parent_id' => $subItem['parent_id'],
                    'sub_item_letter' => $subItem['sub_item_letter']
                ],
                $subItem
            );
        }

        $this->command->info('Schedule sub-items created: ' . count($scheduleSubItems));
    }
}
