<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('pbc_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('category_id')->constrained('pbc_categories')->onDelete('cascade');
            $table->enum('engagement_type', ['audit', 'accounting', 'tax', 'special_engagement', 'others']);
            $table->text('default_description');
            $table->integer('default_days_to_complete')->default(7);
            $table->enum('default_priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->json('required_fields')->nullable(); // JSON structure for additional fields
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('pbc_templates');
    }
};
