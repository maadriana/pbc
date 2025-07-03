<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('pbc_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pbc_request_id')->constrained()->onDelete('cascade');
            $table->foreignId('template_item_id')->nullable()->constrained('pbc_template_items')->onDelete('set null'); // null if custom item
            $table->foreignId('category_id')->constrained('pbc_categories')->onDelete('cascade');
            $table->foreignId('parent_id')->nullable()->constrained('pbc_request_items')->onDelete('cascade'); // for sub-items

            // Item identification (editable copies from template)
            $table->string('item_number')->nullable(); // "1", "2", etc.
            $table->string('sub_item_letter')->nullable(); // "a", "b", "c"
            $table->text('description'); // Editable description
            $table->integer('sort_order')->default(0);

            // Status and tracking
            $table->enum('status', ['pending', 'submitted', 'under_review', 'accepted', 'rejected', 'overdue'])->default('pending');
            $table->date('date_requested')->nullable();
            $table->date('due_date')->nullable();
            $table->date('date_submitted')->nullable();
            $table->date('date_reviewed')->nullable();
            $table->integer('days_outstanding')->default(0); // Auto-calculated

            // Assignment
            $table->foreignId('requested_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');

            // Review information
            $table->text('remarks')->nullable(); // Staff remarks
            $table->text('client_remarks')->nullable(); // Client remarks/questions
            $table->boolean('is_required')->default(true);
            $table->boolean('is_custom')->default(false); // Added by staff, not from template

            $table->timestamps();

            // Indexes for performance
            $table->index(['pbc_request_id', 'status']);
            $table->index(['assigned_to', 'status']);
            $table->index(['due_date']);
            $table->index(['category_id', 'sort_order']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('pbc_request_items');
    }
};
