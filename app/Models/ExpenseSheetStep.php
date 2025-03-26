<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpenseSheetStep extends Model
{
    protected $fillable = [
        'expense_sheet_cost_id',
        'address',
        'order',
    ];

    public function cost()
    {
        return $this->belongsTo(ExpenseSheetCost::class, 'expense_sheet_cost_id');
    }
}
