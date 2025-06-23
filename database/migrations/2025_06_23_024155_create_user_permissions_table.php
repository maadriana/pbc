<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('user_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('permission'); // create_user, edit_client, delete_pbc_request, etc.
            $table->string('resource')->nullable(); // specific resource if applicable
            $table->timestamps();

            $table->unique(['user_id', 'permission', 'resource']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_permissions');
    }
};
