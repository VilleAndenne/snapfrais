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
        Schema::table('department_user', function (Blueprint $table) {
            $table->boolean('is_encoder')->default(false)->after('is_head');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('department_user', function (Blueprint $table) {
            $table->dropColumn('is_encoder');
        });
    }
};
