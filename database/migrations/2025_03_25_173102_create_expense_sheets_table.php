<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('expense_sheets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('distance', 8, 2)->nullable(); // Nombre de kilomètres
            $table->json('route')->nullable(); // Stocker les étapes sous forme JSON
            $table->decimal('total', 10, 2)->nullable(); // Montant total du remboursement
            $table->string('status')->default('En attente');
            $table->timestamp('validated_at')->nullable();
            $table->foreignId('validated_by')->nullable()->constrained('users');
            $table->boolean('approved')->nullable();
            $table->string('refusal_reason')->nullable();
            $table->foreignId('department_id')->nullable()->constrained();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('expense_sheets');
    }
};
