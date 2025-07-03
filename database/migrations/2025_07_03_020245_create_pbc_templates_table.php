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
            $table->string('name'); // "AT-700", "AT-690"
            $table->string('code')->unique(); // "at_700", "at_690"
            $table->text('description')->nullable();
            $table->json('engagement_types')->nullable(); // ["audit", "accounting"] - which engagement types can use this template
            $table->boolean('is_default')->default(false);
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
