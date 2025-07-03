<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('pbc_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pbc_request_item_id')->constrained()->onDelete('cascade');
            $table->foreignId('pbc_request_id')->constrained()->onDelete('cascade'); // For easy querying

            // File information
            $table->string('original_filename');
            $table->string('stored_filename'); // UUID-based filename for security
            $table->string('file_path'); // Path in storage
            $table->string('mime_type');
            $table->integer('file_size'); // in bytes
            $table->string('file_hash')->nullable(); // For duplicate detection

            // Submission tracking
            $table->foreignId('uploaded_by')->constrained('users')->onDelete('cascade');
            $table->timestamp('uploaded_at');
            $table->enum('status', ['pending', 'under_review', 'accepted', 'rejected'])->default('pending');

            // Review information
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_remarks')->nullable();
            $table->enum('review_action', ['approve', 'reject', 'request_revision'])->nullable();

            // Version control
            $table->integer('version')->default(1);
            $table->foreignId('replaces_submission_id')->nullable()->constrained('pbc_submissions')->onDelete('set null');

            // Metadata
            $table->json('metadata')->nullable(); // For additional file info
            $table->boolean('is_active')->default(true); // For soft archiving

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['pbc_request_item_id', 'status']);
            $table->index(['uploaded_by', 'status']);
            $table->index(['reviewed_by', 'status']);
            $table->index(['file_hash']); // For duplicate detection
        });
    }

    public function down()
    {
        Schema::dropIfExists('pbc_submissions');
    }
};
