<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PbcCategory;

class PbcCategorySeeder extends Seeder
{
    public function run()
    {
        $categories = [
            ['name' => 'Cash and Cash Equivalents', 'code' => 'CASH', 'description' => 'Bank statements, reconciliations, cash counts', 'color_code' => '#10B981'],
            ['name' => 'Accounts Receivable', 'code' => 'AR', 'description' => 'Customer aging, confirmations, collections', 'color_code' => '#3B82F6'],
            ['name' => 'Inventory', 'code' => 'INV', 'description' => 'Inventory counts, costing, movements', 'color_code' => '#F59E0B'],
            ['name' => 'Property, Plant & Equipment', 'code' => 'PPE', 'description' => 'Fixed asset registers, depreciation, additions', 'color_code' => '#8B5CF6'],
            ['name' => 'Accounts Payable', 'code' => 'AP', 'description' => 'Vendor aging, confirmations, accruals', 'color_code' => '#EF4444'],
            ['name' => 'Payroll', 'code' => 'PAYROLL', 'description' => 'Payroll registers, tax filings, benefits', 'color_code' => '#06B6D4'],
            ['name' => 'Revenue', 'code' => 'REV', 'description' => 'Sales registers, contracts, cut-off testing', 'color_code' => '#84CC16'],
            ['name' => 'Expenses', 'code' => 'EXP', 'description' => 'Operating expenses, accruals, prepayments', 'color_code' => '#F97316'],
            ['name' => 'Tax', 'code' => 'TAX', 'description' => 'Tax returns, assessments, computations', 'color_code' => '#EC4899'],
            ['name' => 'General Ledger', 'code' => 'GL', 'description' => 'Trial balance, journal entries, reconciliations', 'color_code' => '#6B7280'],
            ['name' => 'Financial Statements', 'code' => 'FS', 'description' => 'Draft financial statements, notes, disclosures', 'color_code' => '#1F2937'],
            ['name' => 'Corporate', 'code' => 'CORP', 'description' => 'Board resolutions, contracts, legal documents', 'color_code' => '#7C2D12'],
        ];

        foreach ($categories as $category) {
            PbcCategory::create($category);
        }
    }
}
