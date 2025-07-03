<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->enum('engagement_type', ['audit', 'accounting', 'tax', 'special_engagement', 'others']);
            $table->date('engagement_period');
            $table->string('contact_person');
            $table->string('contact_email');
            $table->string('contact_number');
            $table->foreignId('engagement_partner_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('manager_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('associate_1_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('associate_2_id')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('status', ['active', 'completed', 'on_hold', 'cancelled'])->default('active');
            $table->decimal('progress_percentage', 5, 2)->default(0);

            // NEW: PBC-related fields
            $table->integer('total_pbc_requests')->default(0);
            $table->integer('completed_pbc_requests')->default(0);
            $table->decimal('pbc_completion_percentage', 5, 2)->default(0);
            $table->date('pbc_deadline')->nullable(); // Overall PBC deadline
            $table->enum('pbc_status', ['not_started', 'in_progress', 'completed', 'overdue'])->default('not_started');
            $table->json('pbc_settings')->nullable(); // Project-specific PBC settings

            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('projects');
    }
};
