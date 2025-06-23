<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Add soft deletes to tables that use SoftDeletes trait

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        Schema::table('clients', function (Blueprint $table) {
            if (!Schema::hasColumn('clients', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        Schema::table('projects', function (Blueprint $table) {
            if (!Schema::hasColumn('projects', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        Schema::table('pbc_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('pbc_requests', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        Schema::table('pbc_categories', function (Blueprint $table) {
            if (!Schema::hasColumn('pbc_categories', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        Schema::table('pbc_documents', function (Blueprint $table) {
            if (!Schema::hasColumn('pbc_documents', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        Schema::table('pbc_comments', function (Blueprint $table) {
            if (!Schema::hasColumn('pbc_comments', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        Schema::table('pbc_templates', function (Blueprint $table) {
            if (!Schema::hasColumn('pbc_templates', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('pbc_requests', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('pbc_categories', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('pbc_documents', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('pbc_comments', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('pbc_templates', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
