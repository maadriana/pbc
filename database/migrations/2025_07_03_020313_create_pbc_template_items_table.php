<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('pbc_template_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('pbc_templates')->onDelete('cascade');
            $table->foreignId('category_id')->constrained('pbc_categories')->onDelete('cascade');
            $table->foreignId('parent_id')->nullable()->constrained('pbc_template_items')->onDelete('cascade'); // for sub-items
            $table->string('item_number')->nullable(); // "1", "2", etc. for main items
            $table->string('sub_item_letter')->nullable(); // "a", "b", "c" for sub-items
            $table->text('description'); // The actual checklist item description
            $table->integer('sort_order')->default(0);
            $table->boolean('is_required')->default(true);
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable(); // for any additional data
            $table->timestamps();

            // Ensure proper ordering
            $table->index(['template_id', 'category_id', 'sort_order']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('pbc_template_items');
    }
};
