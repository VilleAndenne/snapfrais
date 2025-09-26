<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpenseSheetCost extends Model
{
    protected $fillable = [
        'expense_sheet_id',
        'form_cost_id',
        'type',
        'distance',
        'google_distance',
        'route',
        'requirements',
        'total',
        'amount',
        'date',
    ];

    protected $casts = [
        'route' => 'array',
        'requirements' => 'array',
    ];

    public function expenseSheet()
    {
        return $this->belongsTo(ExpenseSheet::class);
    }

    public function formCost()
    {
        return $this->belongsTo(FormCost::class);
    }

    public function exports()
    {
        return $this->belongsToMany(
            \App\Models\ExpenseSheetExport::class,
            'expense_sheet_export_expense_sheets'
        )->withTimestamps();
    }
}
