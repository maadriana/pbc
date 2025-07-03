<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('sec_registration_no')->unique();
            $table->string('industry_classification');
            $table->text('business_address');
            $table->string('primary_contact_name');
            $table->string('primary_contact_email');
            $table->string('primary_contact_number');
            $table->string('secondary_contact_name')->nullable();
            $table->string('secondary_contact_email')->nullable();
            $table->string('secondary_contact_number')->nullable();
            $table->boolean('is_active')->default(true);

            // NEW: PBC-related tracking fields
            $table->integer('total_pbc_requests')->default(0);
            $table->integer('pending_pbc_requests')->default(0);
            $table->decimal('average_pbc_completion_rate', 5, 2)->default(0);
            $table->timestamp('last_pbc_activity')->nullable();
            $table->json('pbc_preferences')->nullable(); // Client-specific preferences
            $table->text('special_instructions')->nullable(); // Special handling instructions

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('clients');
    }
};
