<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('pbc_reminders', function (Blueprint $table) {
            $table->id();

            // What is being reminded about
            $table->morphs('remindable'); // Can remind about requests, items, or submissions (this already creates the index)

            // Reminder details
            $table->string('subject');
            $table->text('message');
            $table->enum('type', ['initial', 'follow_up', 'urgent', 'final_notice'])->default('follow_up');
            $table->enum('method', ['email', 'sms', 'system'])->default('email');

            // Timing
            $table->timestamp('scheduled_at'); // When to send
            $table->timestamp('sent_at')->nullable(); // When actually sent
            $table->integer('days_before_due')->nullable(); // How many days before due date

            // Participants
            $table->foreignId('sent_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('sent_to')->constrained('users')->onDelete('cascade');

            // Status and tracking
            $table->enum('status', ['scheduled', 'sent', 'failed', 'cancelled'])->default('scheduled');
            $table->text('delivery_details')->nullable(); // Success/failure details
            $table->timestamp('read_at')->nullable(); // When recipient read it

            // Auto-reminder settings
            $table->boolean('is_auto')->default(false); // System-generated vs manual
            $table->json('auto_settings')->nullable(); // Settings for auto-reminders

            $table->timestamps();

            // Indexes (removed the duplicate remindable index since morphs() already creates it)
            $table->index(['sent_to', 'status']);
            $table->index(['scheduled_at', 'status']);
            $table->index(['sent_by', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('pbc_reminders');
    }
};
