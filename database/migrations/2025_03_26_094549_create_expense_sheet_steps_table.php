<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('expense_sheet_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_sheet_cost_id')->constrained()->onDelete('cascade');
            $table->string('address');
            $table->unsignedInteger('order'); // pour garder l'ordre des étapes
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expense_sheet_steps');
    }
};
