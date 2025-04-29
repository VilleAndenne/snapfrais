<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Remove the foreign key constraint if it exists
            if (Schema::hasColumn('users', 'organization_id')) {
                $table->dropForeign(['organization_id']);
            }

            // Drop the organization_id column
            $table->dropColumn('organization_id');
        });

        Schema::table('forms', function (Blueprint $table) {
            // Remove the foreign key constraint if it exists
            if (Schema::hasColumn('forms', 'organization_id')) {
                $table->dropForeign(['organization_id']);
            }

            // Drop the organization_id column
            $table->dropColumn('organization_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Add the organization_id column back
            $table->foreignId('organization_id')->constrained()->after('id');
        });

        Schema::table('forms', function (Blueprint $table) {
            // Add the organization_id column back
            $table->foreignId('organization_id')->constrained()->after('id');
        });
    }
};
