<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpenseSheetStep extends Model
{
    protected $fillable = [
        'expense_sheet_id',
        'start_point',
        'end_point',
        'distance',
    ];

    public function expenseSheet()
    {
        return $this->belongsTo(ExpenseSheet::class);
    }
}
