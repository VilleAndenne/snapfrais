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
        Schema::table('form_cost_remboursiement_rates', function (Blueprint $table) {
            $table->string('transport')->default('car')->after('value');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('form_cost_remboursiement_rates', function (Blueprint $table) {
            $table->dropColumn('transport');
        });
    }
};
