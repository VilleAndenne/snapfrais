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
        Schema::create('route_distances', function (Blueprint $table) {
            $table->id();
            $table->char('signature', 64)->unique();
            $table->string('origin', 500);
            $table->string('destination', 500);
            $table->string('transport', 20);
            $table->unsignedInteger('distance_meters');
            $table->unsignedInteger('last_anomaly_meters')->nullable();
            $table->timestamp('last_anomaly_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('route_distances');
    }
};
