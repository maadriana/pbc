<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('entity')->nullable();
            $table->enum('role', ['system_admin', 'engagement_partner', 'manager', 'associate', 'guest']);
            $table->enum('access_level', [1, 2, 3, 4, 5])->default(5); // 1 = highest (system admin), 5 = lowest (guest)
            $table->string('contact_number')->nullable();
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('users');
    }
};
