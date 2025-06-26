<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('pbc_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('pbc_conversations')->onDelete('cascade');
            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
            $table->text('message')->nullable();
            $table->json('attachments')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->enum('message_type', ['text', 'file', 'system'])->default('text');
            $table->foreignId('reply_to_id')->nullable()->constrained('pbc_messages')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['conversation_id', 'created_at']);
            $table->index(['sender_id']);
            $table->index(['is_read', 'sender_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('pbc_messages');
    }
};
