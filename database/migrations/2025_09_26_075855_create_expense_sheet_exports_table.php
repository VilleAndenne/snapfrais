<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('expense_sheet_exports', function (Blueprint $table) {
            $table->id();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status')->default('pending');
            $table->string('file_path')->nullable();
            $table->timestamps();
        });

        Schema::create('expense_sheet_export_expense_sheets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_sheet_export_id')->constrained('expense_sheet_exports')->onDelete('cascade');
            $table->foreignId('expense_sheet_id')->constrained('expense_sheets')->onDelete('cascade');
            $table->timestamps();
        });


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expense_sheet_export_expense_sheets');
        Schema::dropIfExists('expense_sheet_exports');
    }
};
