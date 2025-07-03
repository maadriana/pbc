<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();

            // Who performed the action
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('user_email')->nullable(); // Backup in case user is deleted
            $table->string('user_name')->nullable(); // Backup in case user is deleted

            // What action was performed
            $table->string('action'); // 'created', 'updated', 'deleted', 'uploaded', 'approved', etc.
            $table->string('model_type')->nullable(); // Model class name
            $table->unsignedBigInteger('model_id')->nullable(); // Model ID
            $table->text('description'); // Human-readable description

            // Context and details
            $table->json('old_values')->nullable(); // Before values
            $table->json('new_values')->nullable(); // After values
            $table->json('metadata')->nullable(); // Additional context data

            // Request context
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('url')->nullable();
            $table->string('method')->nullable(); // GET, POST, PUT, DELETE

            // Categorization
            $table->enum('category', ['user', 'client', 'project', 'pbc_request', 'document', 'system'])->default('system');
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium');

            $table->timestamps();

            // Indexes for performance
            $table->index(['user_id', 'created_at']);
            $table->index(['model_type', 'model_id']);
            $table->index(['action', 'created_at']);
            $table->index(['category', 'created_at']);
            $table->index(['created_at']); // For cleanup/archival
        });
    }

    public function down()
    {
        Schema::dropIfExists('audit_logs');
    }
};
