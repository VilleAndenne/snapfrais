<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La table conserve la *dernière* mesure d'un trajet, pas une référence figée :
 * les colonnes sont renommées en conséquence, et `last_used_at` disparaît —
 * chaque utilisation étant désormais une mesure, elle ferait double emploi
 * avec `measured_at`.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('route_distances', function (Blueprint $table) {
            $table->renameColumn('verified_at', 'measured_at');
            $table->renameColumn('last_anomaly_meters', 'previous_distance_meters');
        });

        Schema::table('route_distances', function (Blueprint $table) {
            $table->dropColumn('last_used_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('route_distances', function (Blueprint $table) {
            $table->renameColumn('measured_at', 'verified_at');
            $table->renameColumn('previous_distance_meters', 'last_anomaly_meters');
        });

        Schema::table('route_distances', function (Blueprint $table) {
            $table->timestamp('last_used_at')->nullable();
        });
    }
};
