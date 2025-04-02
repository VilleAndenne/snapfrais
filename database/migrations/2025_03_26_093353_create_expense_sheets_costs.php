<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('expense_sheet_costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_sheet_id')->constrained()->onDelete('cascade');
            $table->foreignId('form_cost_id')->constrained()->onDelete('cascade');
            $table->string('type'); // 'km', 'fixed', 'percentage'
            $table->float('distance')->nullable(); // total distance (manual + google)
            $table->float('google_distance')->nullable(); // distance calculée via Google Maps
            $table->json('route')->nullable(); // itinéraire complet
            $table->json('requirements')->nullable(); // JSON des besoins
            $table->decimal('total', 10, 2); // montant du remboursement
            $table->date('date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expense_sheets_costs');
    }
};
