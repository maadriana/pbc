<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pbc_documents', function (Blueprint $table) {
            $table->string('cloud_url')->nullable();
            $table->string('cloud_public_id')->nullable();
            $table->string('cloud_provider')->default('local');
            $table->json('metadata')->nullable();
            $table->timestamp('last_accessed_at')->nullable();
        });
    }

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
        });
    }
};
