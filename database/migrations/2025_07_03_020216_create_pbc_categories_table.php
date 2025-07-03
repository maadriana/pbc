<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('pbc_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // "1. Permanent File", "2. Current File"
            $table->string('code')->unique(); // "permanent_file", "current_file"
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('pbc_categories');
    }
};
