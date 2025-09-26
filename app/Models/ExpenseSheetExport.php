<?php

namespace App\Models;

use App\Casts\DateCast;
use Illuminate\Database\Eloquent\Model;

class ExpenseSheetExport extends Model
{
    protected $fillable = [
        'start_date',
        'end_date',
        'file_path',
        'status',
    ];

    protected $casts = [
        'start_date' => DateCast::class,
        'end_date'   => DateCast::class,
    ];

    protected $appends = ['file_url', 'records_count'];

    public function getFileUrlAttribute()
    {
        return \Storage::url($this->attributes['file_path']);
    }

    public function expenseSheets()
    {
        // ⚠️ on précise le nom de la table pivot car il n’est pas le nom par défaut
        return $this->belongsToMany(
            \App\Models\ExpenseSheet::class,
            'expense_sheet_export_expense_sheets' // pivot
        )->withTimestamps();
    }
    protected function getRecordsCountAttribute() {
        return $this->expenseSheets()->count();
    }
}
