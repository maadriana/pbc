<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PbcTemplate;
use App\Models\PbcTemplateItem;
use App\Models\PbcCategory;

class At700TemplateItemsSeeder extends Seeder
{
    public function run()
    {
        // Get the AT-700 template and categories
        $at700Template = PbcTemplate::where('code', 'at_700')->first();
        $permanentFileCategory = PbcCategory::where('code', 'permanent_file')->first();
        $currentFileCategory = PbcCategory::where('code', 'current_file')->first();

        if (!$at700Template || !$permanentFileCategory || !$currentFileCategory) {
            $this->command->error('Required templates or categories not found. Please run other seeders first.');
            return;
        }

        // Clear existing items for this template
        PbcTemplateItem::where('template_id', $at700Template->id)->delete();

        $items = $this->getAt700Items($at700Template->id, $permanentFileCategory->id, $currentFileCategory->id);

        foreach ($items as $item) {
            PbcTemplateItem::create($item);
        }

        $this->command->info('AT-700 Template Items seeded successfully!');
        $this->command->info('Total items created: ' . count($items));
    }

    private function getAt700Items($templateId, $permanentFileCategoryId, $currentFileCategoryId)
    {
        return [
            // 1. PERMANENT FILE ITEMS
            [
                'template_id' => $templateId,
                'category_id' => $permanentFileCategoryId,
                'parent_id' => null,
                'item_number' => '1',
                'sub_item_letter' => null,
                'description' => 'Latest Articles of Incorporation and By-laws',
                'sort_order' => 1,
                'is_required' => true,
                'is_active' => true,
            ],
            [
                'template_id' => $templateId,
                'category_id' => $permanentFileCategoryId,
                'parent_id' => null,
                'item_number' => '2',
                'sub_item_letter' => null,
                'description' => 'BIR Certificate of Registration',
                'sort_order' => 2,
                'is_required' => true,
                'is_active' => true,
            ],
            [
                'template_id' => $templateId,
                'category_id' => $permanentFileCategoryId,
                'parent_id' => null,
                'item_number' => '3',
                'sub_item_letter' => null,
                'description' => 'Latest General Information Sheet filed with the SEC',
                'sort_order' => 3,
                'is_required' => true,
                'is_active' => true,
            ],
            [
                'template_id' => $templateId,
                'category_id' => $permanentFileCategoryId,
                'parent_id' => null,
                'item_number' => '4',
                'sub_item_letter' => null,
                'description' => 'Stock transfer book',
                'sort_order' => 4,
                'is_required' => true,
                'is_active' => true,
            ],
            [
                'template_id' => $templateId,
                'category_id' => $permanentFileCategoryId,
                'parent_id' => null,
                'item_number' => '5',
                'sub_item_letter' => null,
                'description' => 'Minutes of meetings of the stockholders, board of directors, and executive committee held during the period from January 1, ____ to date.',
                'sort_order' => 5,
                'is_required' => true,
                'is_active' => true,
            ],

            // Item 6 with sub-items
            [
                'template_id' => $templateId,
                'category_id' => $permanentFileCategoryId,
                'parent_id' => null,
                'item_number' => '6',
                'sub_item_letter' => null,
                'description' => 'Contracts and other agreements with accounting significance held/entered into during the year such as but not limited to:',
                'sort_order' => 6,
                'is_required' => true,
                'is_active' => true,
            ],

            // Item 7
            [
                'template_id' => $templateId,
                'category_id' => $permanentFileCategoryId,
                'parent_id' => null,
                'item_number' => '7',
                'sub_item_letter' => null,
                'description' => 'Completed Letters to Lawyer and to the Corporate Secretary using the Company\'s letterhead',
                'sort_order' => 10,
                'is_required' => true,
                'is_active' => true,
            ],
            [
                'template_id' => $templateId,
                'category_id' => $permanentFileCategoryId,
                'parent_id' => null,
                'item_number' => '8',
                'sub_item_letter' => null,
                'description' => 'Pending tax assessments, if any',
                'sort_order' => 11,
                'is_required' => false,
                'is_active' => true,
            ],
            [
                'template_id' => $templateId,
                'category_id' => $permanentFileCategoryId,
                'parent_id' => null,
                'item_number' => '9',
                'sub_item_letter' => null,
                'description' => 'Prior year/s audited financial statement',
                'sort_order' => 12,
                'is_required' => true,
                'is_active' => true,
            ],

            // 2. CURRENT FILE ITEMS
            [
                'template_id' => $templateId,
                'category_id' => $currentFileCategoryId,
                'parent_id' => null,
                'item_number' => '1',
                'sub_item_letter' => null,
                'description' => 'Trial Balance as of the balance sheet date',
                'sort_order' => 20,
                'is_required' => true,
                'is_active' => true,
            ],

            // Item 2 with sub-items
            [
                'template_id' => $templateId,
                'category_id' => $currentFileCategoryId,
                'parent_id' => null,
                'item_number' => '2',
                'sub_item_letter' => null,
                'description' => 'Schedule of accounts',
                'sort_order' => 21,
                'is_required' => true,
                'is_active' => true,
            ],

            // Additional items from the Excel file analysis
            [
                'template_id' => $templateId,
                'category_id' => $currentFileCategoryId,
                'parent_id' => null,
                'item_number' => '3',
                'sub_item_letter' => null,
                'description' => 'Bank statements and reconciliations for all accounts',
                'sort_order' => 35,
                'is_required' => true,
                'is_active' => true,
            ],
            [
                'template_id' => $templateId,
                'category_id' => $currentFileCategoryId,
                'parent_id' => null,
                'item_number' => '4',
                'sub_item_letter' => null,
                'description' => 'Accounts receivable aging and analysis',
                'sort_order' => 36,
                'is_required' => true,
                'is_active' => true,
            ],
            [
                'template_id' => $templateId,
                'category_id' => $currentFileCategoryId,
                'parent_id' => null,
                'item_number' => '5',
                'sub_item_letter' => null,
                'description' => 'Inventory count sheets and supporting documentation',
                'sort_order' => 37,
                'is_required' => true,
                'is_active' => true,
            ],
            [
                'template_id' => $templateId,
                'category_id' => $currentFileCategoryId,
                'parent_id' => null,
                'item_number' => '6',
                'sub_item_letter' => null,
                'description' => 'Fixed asset register and depreciation schedules',
                'sort_order' => 38,
                'is_required' => true,
                'is_active' => true,
            ],
            [
                'template_id' => $templateId,
                'category_id' => $currentFileCategoryId,
                'parent_id' => null,
                'item_number' => '7',
                'sub_item_letter' => null,
                'description' => 'Accounts payable aging and vendor statements',
                'sort_order' => 39,
                'is_required' => true,
                'is_active' => true,
            ],
            [
                'template_id' => $templateId,
                'category_id' => $currentFileCategoryId,
                'parent_id' => null,
                'item_number' => '8',
                'sub_item_letter' => null,
                'description' => 'Payroll registers and tax returns',
                'sort_order' => 40,
                'is_required' => true,
                'is_active' => true,
            ],
            [
                'template_id' => $templateId,
                'category_id' => $currentFileCategoryId,
                'parent_id' => null,
                'item_number' => '9',
                'sub_item_letter' => null,
                'description' => 'General ledger and journal entries',
                'sort_order' => 41,
                'is_required' => true,
                'is_active' => true,
            ],
            [
                'template_id' => $templateId,
                'category_id' => $currentFileCategoryId,
                'parent_id' => null,
                'item_number' => '10',
                'sub_item_letter' => null,
                'description' => 'Income tax returns and supporting schedules',
                'sort_order' => 42,
                'is_required' => true,
                'is_active' => true,
            ],
        ];
    }

    // We'll create sub-items in a separate method to handle parent_id relationships
    private function createSubItems($templateId, $permanentFileCategoryId, $currentFileCategoryId)
    {
        // Get parent items
        $contractsItem = PbcTemplateItem::where('template_id', $templateId)
            ->where('item_number', '6')
            ->where('category_id', $permanentFileCategoryId)
            ->first();

        $schedulesItem = PbcTemplateItem::where('template_id', $templateId)
            ->where('item_number', '2')
            ->where('category_id', $currentFileCategoryId)
            ->first();

        if ($contractsItem) {
            $subItems = [
                [
                    'template_id' => $templateId,
                    'category_id' => $permanentFileCategoryId,
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
                    'category_id' => $permanentFileCategoryId,
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
                    'category_id' => $permanentFileCategoryId,
                    'parent_id' => $contractsItem->id,
                    'item_number' => null,
                    'sub_item_letter' => 'c',
                    'description' => 'Lease Agreements',
                    'sort_order' => 9,
                    'is_required' => false,
                    'is_active' => true,
                ],
            ];

            foreach ($subItems as $subItem) {
                PbcTemplateItem::create($subItem);
            }
        }

        if ($schedulesItem) {
            $scheduleSubItems = [
                [
                    'template_id' => $templateId,
                    'category_id' => $currentFileCategoryId,
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
                    'category_id' => $currentFileCategoryId,
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
                    'category_id' => $currentFileCategoryId,
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
                    'category_id' => $currentFileCategoryId,
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
                    'category_id' => $currentFileCategoryId,
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
                    'category_id' => $currentFileCategoryId,
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
                    'category_id' => $currentFileCategoryId,
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
                    'category_id' => $currentFileCategoryId,
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
                    'category_id' => $currentFileCategoryId,
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
                    'category_id' => $currentFileCategoryId,
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
                    'category_id' => $currentFileCategoryId,
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
                    'category_id' => $currentFileCategoryId,
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
                    'category_id' => $currentFileCategoryId,
                    'parent_id' => $schedulesItem->id,
                    'item_number' => null,
                    'sub_item_letter' => 'm',
                    'description' => 'Schedule of operating expenses',
                    'sort_order' => 34,
                    'is_required' => true,
                    'is_active' => true,
                ],
            ];

            foreach ($scheduleSubItems as $subItem) {
                PbcTemplateItem::create($subItem);
            }
        }
    }
}
