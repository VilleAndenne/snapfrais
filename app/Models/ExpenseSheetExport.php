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
        'created_by_id',
    ];

    protected $casts = [
        'start_date' => DateCast::class,
        'end_date'   => DateCast::class,
    ];

    protected $appends = ['file_url', 'records_count', 'created_by_name'];

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
    protected function getRecordsCountAttribute(): int
    {
        return $this->expenseSheets()->count();
    }

    public function createdBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    protected function getCreatedByNameAttribute(): string
    {
        return $this->createdBy?->name ?? '-';
    }
}
