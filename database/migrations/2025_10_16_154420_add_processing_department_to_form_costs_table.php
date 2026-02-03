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
        Schema::table('form_costs', function (Blueprint $table) {
            $table->enum('processing_department', ['SRH', 'DSF'])->default('SRH')->after('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('form_costs', function (Blueprint $table) {
            $table->dropColumn('processing_department');
        });
    }
};
