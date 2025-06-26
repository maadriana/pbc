<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('pbc_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->foreignId('project_id')->constrained('projects')->onDelete('cascade');
            $table->string('title')->nullable();
            $table->enum('status', ['active', 'completed', 'archived'])->default('active');
            $table->timestamp('last_message_at')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['client_id', 'project_id']);
            $table->index(['status', 'last_message_at']);
            $table->index('created_by');
        });
    }

    public function down()
    {
        Schema::dropIfExists('pbc_conversations');
    }
};
