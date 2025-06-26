<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pbc_documents', function (Blueprint $table) {
            // Add cloud storage support columns
            $table->string('cloud_url')->nullable()->after('file_path');
            $table->string('cloud_public_id')->nullable()->after('cloud_url');
            $table->string('cloud_provider')->default('local')->after('cloud_public_id');

            // Add metadata columns
            $table->json('metadata')->nullable()->after('cloud_provider');
            $table->timestamp('last_accessed_at')->nullable()->after('metadata');

            // Add indexing for better performance
            $table->index(['status', 'created_at']);
            $table->index(['pbc_request_id', 'status']);
            $table->index(['uploaded_by', 'created_at']);
            $table->index(['file_type', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pbc_documents', function (Blueprint $table) {
            $table->dropColumn([
                'cloud_url',
                'cloud_public_id',
                'cloud_provider',
                'metadata',
                'last_accessed_at'
            ]);

            $table->dropIndex(['status', 'created_at']);
            $table->dropIndex(['pbc_request_id', 'status']);
            $table->dropIndex(['uploaded_by', 'created_at']);
            $table->dropIndex(['file_type', 'status']);
        });
    }
};
