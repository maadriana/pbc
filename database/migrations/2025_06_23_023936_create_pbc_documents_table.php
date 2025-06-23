<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('pbc_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pbc_request_id')->constrained()->onDelete('cascade');
            $table->string('original_name');
            $table->string('file_name');
            $table->string('file_path');
            $table->string('file_type');
            $table->bigInteger('file_size'); // in bytes
            $table->string('mime_type');
            $table->foreignId('uploaded_by')->constrained('users')->onDelete('cascade');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('comments')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('reviewed_at')->nullable();
            $table->string('version', 10)->default('1.0');
            $table->boolean('is_latest_version')->default(true);
            $table->timestamps();

            $table->index(['pbc_request_id', 'is_latest_version']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('pbc_documents');
    }
};
