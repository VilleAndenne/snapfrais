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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('notify_expense_sheet_to_approval')->default(true)->after('email_verified_at');
            $table->boolean('notify_receipt_expense_sheet')->default(true)->after('notify_expense_sheet_to_approval');
            $table->boolean('notify_remind_approval')->default(true)->after('notify_receipt_expense_sheet');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['notify_expense_sheet_to_approval', 'notify_receipt_expense_sheet', 'notify_remind_approval']);
        });
    }
};
