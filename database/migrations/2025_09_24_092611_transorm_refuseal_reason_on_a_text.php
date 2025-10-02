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
        // 1) Ajouter une colonne temporaire en TEXT
        Schema::table('expense_sheets', function (Blueprint $table) {
            $table->text('refusal_reason_tmp')->nullable(); // ou ->default('') si tu veux NOT NULL
        });

        // 2) Copier les données
        DB::table('expense_sheets')->update([
            'refusal_reason_tmp' => DB::raw('refusal_reason'),
        ]);

        // 3) Supprimer l’ancienne colonne
        Schema::table('expense_sheets', function (Blueprint $table) {
            $table->dropColumn('refusal_reason');
        });

        // 4) Renommer la colonne temporaire
        Schema::table('expense_sheets', function (Blueprint $table) {
            $table->renameColumn('refusal_reason_tmp', 'refusal_reason');
        });
    }

    public function down(): void
    {
        // Revenir à string (même pattern, inverse)
        Schema::table('expense_sheets', function (Blueprint $table) {
            $table->string('refusal_reason_old')->nullable(); // adapte NOT NULL/longueur si besoin
        });

        DB::table('expense_sheets')->update([
            'refusal_reason_old' => DB::raw('refusal_reason'),
        ]);

        Schema::table('expense_sheets', function (Blueprint $table) {
            $table->dropColumn('refusal_reason');
        });

        Schema::table('expense_sheets', function (Blueprint $table) {
            $table->renameColumn('refusal_reason_old', 'refusal_reason');
        });
    }
};
