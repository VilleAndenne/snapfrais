<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Coordonnées de paiement de l'agent, reprises dans les demandes de
     * remboursement adressées à la DSF. Stockées chiffrées, d'où le type texte :
     * la valeur chiffrée est bien plus longue que l'IBAN qu'elle contient.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('bank_account_number')->nullable()->after('terms_accepted_at');
            $table->text('address')->nullable()->after('bank_account_number');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['bank_account_number', 'address']);
        });
    }
};
