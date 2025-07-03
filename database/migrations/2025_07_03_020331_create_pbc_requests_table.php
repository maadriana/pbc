<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('pbc_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('template_id')->constrained('pbc_templates')->onDelete('cascade');
            $table->string('title'); // e.g., "AT-700 Annual Audit 2024"

            // Header information from Excel
            $table->string('client_name'); // From project->client but stored for quick access
            $table->string('audit_period'); // From project but can be customized
            $table->string('contact_person'); // From project but can be customized
            $table->string('contact_email'); // From project but can be customized
            $table->string('engagement_partner')->nullable();
            $table->string('engagement_manager')->nullable();
            $table->date('document_date');

            // Progress and status
            $table->enum('status', ['draft', 'active', 'completed', 'cancelled'])->default('draft');
            $table->decimal('completion_percentage', 5, 2)->default(0); // Based on accepted files
            $table->integer('total_items')->default(0);
            $table->integer('completed_items')->default(0);
            $table->integer('pending_items')->default(0);
            $table->integer('overdue_items')->default(0);

            // Assignment and tracking
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null'); // Main assignee (usually client contact)
            $table->date('due_date')->nullable();
            $table->date('completed_at')->nullable();

            // Notes and remarks
            $table->text('notes')->nullable(); // Internal notes for staff
            $table->text('client_notes')->nullable(); // Notes visible to client
            $table->text('status_note')->nullable(); // Visible to both sides as you requested

            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['project_id', 'status']);
            $table->index(['assigned_to', 'status']);
            $table->index(['due_date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('pbc_requests');
    }
};
