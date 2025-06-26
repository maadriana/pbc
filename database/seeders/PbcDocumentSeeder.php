<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PbcDocument;
use App\Models\PbcRequest;
use App\Models\User;
use Carbon\Carbon;

class PbcDocumentSeeder extends Seeder
{
    public function run()
    {
        $pbcRequests = PbcRequest::all();
        $users = User::whereIn('role', ['system_admin', 'engagement_partner', 'manager', 'associate', 'guest'])->get();

        if ($pbcRequests->count() < 3 || $users->count() < 3) {
            $this->command->warn('⚠️ Skipping PbcDocumentSeeder: insufficient PBC requests or users.');
            return;
        }

        $sampleDocuments = [
            [
                'pbc_request_id' => $pbcRequests[0]->id,
                'original_name' => 'Bank_Statement_January_2024.pdf',
                'file_name' => 'bank_statement_jan_2024_' . uniqid() . '.pdf',
                'file_path' => 'pbc-documents/2024/01/bank_statement_jan_2024_' . uniqid() . '.pdf',
                'file_type' => 'pdf',
                'file_size' => 2048000, // 2MB
                'mime_type' => 'application/pdf',
                'uploaded_by' => $users->where('role', 'guest')->first()->id,
                'status' => 'pending',
                'comments' => 'Bank statement for January 2024 - BPI Checking Account',
                'version' => '1.0',
                'is_latest_version' => true,
                'cloud_provider' => 'local',
                'metadata' => [
                    'original_size' => 2048000,
                    'mime_type' => 'application/pdf',
                    'upload_method' => 'web_interface',
                    'cloud_backup' => false,
                    'upload_ip' => '127.0.0.1',
                ],
                'created_at' => Carbon::now()->subDays(8),
                'updated_at' => Carbon::now()->subDays(8),
                'last_accessed_at' => Carbon::now()->subDays(2),
            ],
            [
                'pbc_request_id' => $pbcRequests[0]->id,
                'original_name' => 'Bank_Statement_February_2024.pdf',
                'file_name' => 'bank_statement_feb_2024_' . uniqid() . '.pdf',
                'file_path' => 'pbc-documents/2024/02/bank_statement_feb_2024_' . uniqid() . '.pdf',
                'file_type' => 'pdf',
                'file_size' => 1856000, // 1.8MB
                'mime_type' => 'application/pdf',
                'uploaded_by' => $users->where('role', 'guest')->first()->id,
                'status' => 'approved',
                'comments' => 'Bank statement for February 2024 - BPI Checking Account',
                'version' => '1.0',
                'is_latest_version' => true,
                'reviewed_by' => $users->where('role', 'manager')->first()->id,
                'reviewed_at' => Carbon::now()->subDays(1),
                'cloud_provider' => 'local',
                'metadata' => [
                    'original_size' => 1856000,
                    'mime_type' => 'application/pdf',
                    'upload_method' => 'web_interface',
                    'cloud_backup' => false,
                    'upload_ip' => '127.0.0.1',
                ],
                'created_at' => Carbon::now()->subDays(6),
                'updated_at' => Carbon::now()->subDays(1),
                'last_accessed_at' => Carbon::now()->subHours(5),
            ],
            [
                'pbc_request_id' => $pbcRequests[1]->id,
                'original_name' => 'AR_Aging_Report_Dec_2024.xlsx',
                'file_name' => 'ar_aging_dec_2024_' . uniqid() . '.xlsx',
                'file_path' => 'pbc-documents/2024/12/ar_aging_dec_2024_' . uniqid() . '.xlsx',
                'file_type' => 'xlsx',
                'file_size' => 512000, // 512KB
                'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'uploaded_by' => $users->where('role', 'guest')->first()->id,
                'status' => 'approved',
                'comments' => 'AR aging report as of December 31, 2024',
                'version' => '2.0',
                'is_latest_version' => true,
                'reviewed_by' => $users->where('role', 'manager')->first()->id,
                'reviewed_at' => Carbon::now()->subDays(2),
                'cloud_provider' => 'local',
                'metadata' => [
                    'original_size' => 512000,
                    'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'upload_method' => 'web_interface',
                    'cloud_backup' => false,
                    'upload_ip' => '127.0.0.1',
                ],
                'created_at' => Carbon::now()->subDays(4),
                'updated_at' => Carbon::now()->subDays(2),
                'last_accessed_at' => Carbon::now()->subHours(3),
            ],
            [
                'pbc_request_id' => $pbcRequests[2]->id,
                'original_name' => 'Trial_Balance_2024.pdf',
                'file_name' => 'trial_balance_2024_' . uniqid() . '.pdf',
                'file_path' => 'pbc-documents/2024/12/trial_balance_2024_' . uniqid() . '.pdf',
                'file_type' => 'pdf',
                'file_size' => 3072000, // 3MB
                'mime_type' => 'application/pdf',
                'uploaded_by' => $users->where('role', 'guest')->first()->id,
                'status' => 'rejected',
                'comments' => 'Trial balance with supporting schedules - REJECTED: Missing GL details',
                'version' => '1.0',
                'is_latest_version' => true,
                'reviewed_by' => $users->where('role', 'manager')->first()->id,
                'reviewed_at' => Carbon::now()->subDays(3),
                'cloud_provider' => 'local',
                'metadata' => [
                    'original_size' => 3072000,
                    'mime_type' => 'application/pdf',
                    'upload_method' => 'web_interface',
                    'cloud_backup' => false,
                    'upload_ip' => '127.0.0.1',
                ],
                'created_at' => Carbon::now()->subDays(20),
                'updated_at' => Carbon::now()->subDays(3),
                'last_accessed_at' => Carbon::now()->subDays(1),
            ],
            [
                'pbc_request_id' => $pbcRequests[3]->id ?? $pbcRequests[0]->id,
                'original_name' => 'BIR_2316_Summary_2024.pdf',
                'file_name' => 'bir_2316_summary_' . uniqid() . '.pdf',
                'file_path' => 'pbc-documents/2024/04/bir_2316_summary_' . uniqid() . '.pdf',
                'file_type' => 'pdf',
                'file_size' => 1024000, // 1MB
                'mime_type' => 'application/pdf',
                'uploaded_by' => $users->where('role', 'guest')->last()->id,
                'status' => 'pending',
                'comments' => 'BIR 2316 summary for all employees',
                'version' => '1.0',
                'is_latest_version' => true,
                'cloud_provider' => 'local',
                'metadata' => [
                    'original_size' => 1024000,
                    'mime_type' => 'application/pdf',
                    'upload_method' => 'web_interface',
                    'cloud_backup' => false,
                    'upload_ip' => '127.0.0.1',
                ],
                'created_at' => Carbon::now()->subDays(12),
                'updated_at' => Carbon::now()->subDays(12),
                'last_accessed_at' => Carbon::now()->subDays(5),
            ],
            [
                'pbc_request_id' => $pbcRequests[4]->id ?? $pbcRequests[1]->id,
                'original_name' => 'Payroll_Register_December_2024.xlsx',
                'file_name' => 'payroll_register_dec_' . uniqid() . '.xlsx',
                'file_path' => 'pbc-documents/2024/12/payroll_register_dec_' . uniqid() . '.xlsx',
                'file_type' => 'xlsx',
                'file_size' => 768000, // 768KB
                'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'uploaded_by' => $users->where('role', 'guest')->last()->id,
                'status' => 'approved',
                'comments' => 'December 2024 payroll register with government contributions',
                'version' => '1.0',
                'is_latest_version' => true,
                'reviewed_by' => $users->where('role', 'manager')->first()->id,
                'reviewed_at' => Carbon::now()->subDays(4),
                'cloud_provider' => 'local',
                'metadata' => [
                    'original_size' => 768000,
                    'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'upload_method' => 'web_interface',
                    'cloud_backup' => false,
                    'upload_ip' => '127.0.0.1',
                ],
                'created_at' => Carbon::now()->subDays(10),
                'updated_at' => Carbon::now()->subDays(4),
                'last_accessed_at' => Carbon::now()->subHours(8),
            ],
            [
                'pbc_request_id' => $pbcRequests[5]->id ?? $pbcRequests[2]->id,
                'original_name' => 'Inventory_Count_Sheets.pdf',
                'file_name' => 'inventory_count_' . uniqid() . '.pdf',
                'file_path' => 'pbc-documents/2024/12/inventory_count_' . uniqid() . '.pdf',
                'file_type' => 'pdf',
                'file_size' => 4096000, // 4MB
                'mime_type' => 'application/pdf',
                'uploaded_by' => $users->where('role', 'guest')->first()->id,
                'status' => 'pending',
                'comments' => 'Physical inventory count sheets - December 31, 2024',
                'version' => '1.0',
                'is_latest_version' => true,
                'cloud_provider' => 'local',
                'metadata' => [
                    'original_size' => 4096000,
                    'mime_type' => 'application/pdf',
                    'upload_method' => 'web_interface',
                    'cloud_backup' => false,
                    'upload_ip' => '127.0.0.1',
                ],
                'created_at' => Carbon::now()->subDays(18),
                'updated_at' => Carbon::now()->subDays(18),
                'last_accessed_at' => Carbon::now()->subDays(10),
            ],
        ];

        foreach ($sampleDocuments as $document) {
            PbcDocument::create($document);
        }

        $this->command->info('✅ PBC Documents seeded successfully!');
        $this->command->info('📊 Created ' . count($sampleDocuments) . ' sample documents');
    }
}
