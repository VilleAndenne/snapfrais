<?php

return new class extends Migration {
    public function up()
    {
        Schema::create('expense_sheet_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_sheet_id')->constrained()->onDelete('cascade');
            $table->string('start_point');
            $table->string('end_point');
            $table->decimal('distance', 8, 2)->nullable(); // Distance entre deux étapes
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('expense_sheet_steps');
    }
};
