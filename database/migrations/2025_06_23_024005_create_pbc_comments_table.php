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
            $table->foreignId('pbc_request_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('comment');
            $table->boolean('is_internal')->default(false); // true for internal audit team comments
            $table->foreignId('parent_id')->nullable()->constrained('pbc_comments')->onDelete('cascade'); // for replies
            $table->json('attachments')->nullable(); // store file paths as JSON
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('pbc_comments');
    }
};
