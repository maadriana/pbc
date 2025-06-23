<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PbcTemplate;
use App\Models\PbcCategory;
use App\Models\User;

class PbcTemplateSeeder extends Seeder
{
    public function run()
    {
        $systemAdmin = User::where('role', 'system_admin')->first();
        $categories = PbcCategory::all()->keyBy('code');

        $templates = [
            // Cash Templates
            [
                'name' => 'Bank Statements',
                'description' => 'Monthly bank statements for all accounts',
                'category_id' => $categories['CASH']->id,
                'engagement_type' => 'audit',
                'default_description' => 'Please provide monthly bank statements for all bank accounts for the period under audit. Include statements for checking, savings, and time deposit accounts.',
                'default_days_to_complete' => 5,
                'default_priority' => 'high',
                'required_fields' => json_encode([
                    'account_numbers' => 'required',
                    'bank_names' => 'required',
                    'period_covered' => 'required'
                ]),
                'created_by' => $systemAdmin->id,
            ],
            [
                'name' => 'Bank Reconciliations',
                'description' => 'Monthly bank reconciliations for all accounts',
                'category_id' => $categories['CASH']->id,
                'engagement_type' => 'audit',
                'default_description' => 'Please provide monthly bank reconciliations for all bank accounts showing book balance to bank balance reconciliation.',
                'default_days_to_complete' => 7,
                'default_priority' => 'high',
                'required_fields' => json_encode([
                    'reconciliation_format' => 'required',
                    'outstanding_checks_list' => 'required'
                ]),
                'created_by' => $systemAdmin->id,
            ],

            // AR Templates
            [
                'name' => 'Accounts Receivable Aging',
                'description' => 'Detailed aging analysis of accounts receivable',
                'category_id' => $categories['AR']->id,
                'engagement_type' => 'audit',
                'default_description' => 'Please provide detailed accounts receivable aging report as of period end, showing customer names, amounts, and aging categories (current, 30-60, 61-90, over 90 days).',
                'default_days_to_complete' => 5,
                'default_priority' => 'medium',
                'required_fields' => json_encode([
                    'aging_categories' => 'required',
                    'customer_details' => 'required'
                ]),
                'created_by' => $systemAdmin->id,
            ],
            [
                'name' => 'Customer Confirmations List',
                'description' => 'List of customers for confirmation procedures',
                'category_id' => $categories['AR']->id,
                'engagement_type' => 'audit',
                'default_description' => 'Please provide a complete list of customers with contact details for accounts receivable confirmation procedures.',
                'default_days_to_complete' => 3,
                'default_priority' => 'medium',
                'required_fields' => json_encode([
                    'contact_persons' => 'required',
                    'addresses' => 'required',
                    'email_addresses' => 'required'
                ]),
                'created_by' => $systemAdmin->id,
            ],

            // Inventory Templates
            [
                'name' => 'Physical Inventory Count',
                'description' => 'Physical inventory count sheets and results',
                'category_id' => $categories['INV']->id,
                'engagement_type' => 'audit',
                'default_description' => 'Please provide physical inventory count sheets, final inventory listing, and any adjustments made after the count.',
                'default_days_to_complete' => 10,
                'default_priority' => 'high',
                'required_fields' => json_encode([
                    'count_date' => 'required',
                    'locations' => 'required',
                    'count_teams' => 'required'
                ]),
                'created_by' => $systemAdmin->id,
            ],

            // PPE Templates
            [
                'name' => 'Fixed Asset Register',
                'description' => 'Complete fixed asset register with additions and disposals',
                'category_id' => $categories['PPE']->id,
                'engagement_type' => 'audit',
                'default_description' => 'Please provide complete fixed asset register showing beginning balance, additions, disposals, and ending balance with depreciation details.',
                'default_days_to_complete' => 7,
                'default_priority' => 'medium',
                'required_fields' => json_encode([
                    'asset_categories' => 'required',
                    'depreciation_methods' => 'required',
                    'useful_lives' => 'required'
                ]),
                'created_by' => $systemAdmin->id,
            ],

            // AP Templates
            [
                'name' => 'Accounts Payable Aging',
                'description' => 'Detailed aging analysis of accounts payable',
                'category_id' => $categories['AP']->id,
                'engagement_type' => 'audit',
                'default_description' => 'Please provide detailed accounts payable aging report as of period end, showing vendor names, amounts, and aging categories.',
                'default_days_to_complete' => 5,
                'default_priority' => 'medium',
                'required_fields' => json_encode([
                    'vendor_details' => 'required',
                    'payment_terms' => 'required'
                ]),
                'created_by' => $systemAdmin->id,
            ],

            // Payroll Templates
            [
                'name' => 'Payroll Register',
                'description' => 'Monthly payroll registers and tax filings',
                'category_id' => $categories['PAYROLL']->id,
                'engagement_type' => 'audit',
                'default_description' => 'Please provide monthly payroll registers, government tax filings (BIR, SSS, PhilHealth, Pag-IBIG), and employee benefit computations.',
                'default_days_to_complete' => 7,
                'default_priority' => 'medium',
                'required_fields' => json_encode([
                    'employee_count' => 'required',
                    'tax_filings' => 'required'
                ]),
                'created_by' => $systemAdmin->id,
            ],

            // Tax Templates
            [
                'name' => 'BIR Tax Returns',
                'description' => 'All BIR tax returns filed during the period',
                'category_id' => $categories['TAX']->id,
                'engagement_type' => 'tax',
                'default_description' => 'Please provide all BIR tax returns filed during the period including income tax, VAT, withholding tax, and other applicable taxes.',
                'default_days_to_complete' => 5,
                'default_priority' => 'high',
                'required_fields' => json_encode([
                    'return_types' => 'required',
                    'filing_dates' => 'required'
                ]),
                'created_by' => $systemAdmin->id,
            ],

            // General Ledger Templates
            [
                'name' => 'Trial Balance',
                'description' => 'Detailed trial balance with comparative figures',
                'category_id' => $categories['GL']->id,
                'engagement_type' => 'audit',
                'default_description' => 'Please provide detailed trial balance as of period end with comparative figures from prior year and monthly trial balances.',
                'default_days_to_complete' => 3,
                'default_priority' => 'urgent',
                'required_fields' => json_encode([
                    'account_codes' => 'required',
                    'comparative_figures' => 'required'
                ]),
                'created_by' => $systemAdmin->id,
            ],
        ];

        foreach ($templates as $template) {
            PbcTemplate::create($template);
        }
    }
}
