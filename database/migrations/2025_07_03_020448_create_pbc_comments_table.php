<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('pbc_comments', function (Blueprint $table) {
            $table->id();

            // Polymorphic relationship - can comment on requests, items, or submissions
            $table->morphs('commentable'); // commentable_type, commentable_id (this already creates the index)

            // Comment details
            $table->text('comment');
            $table->enum('type', ['general', 'question', 'clarification', 'issue', 'reminder'])->default('general');
            $table->enum('visibility', ['internal', 'client', 'both'])->default('both'); // Who can see this comment

            // Author and tracking
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('parent_id')->nullable()->constrained('pbc_comments')->onDelete('cascade'); // For threaded comments

            // Status and metadata
            $table->boolean('is_resolved')->default(false);
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->json('attachments')->nullable(); // File attachments to comments

            $table->timestamps();
            $table->softDeletes();

            // Indexes (removed the duplicate commentable index since morphs() already creates it)
            $table->index(['user_id', 'created_at']);
            $table->index(['parent_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('pbc_comments');
    }
};
