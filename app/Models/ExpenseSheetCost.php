<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ExpenseSheetCost extends Model
{
    use HasFactory, LogsActivity;

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

    protected $appends = ['file_url'];

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

    public function getFileUrlAttribute()
    {
        return Storage::url($this->requirements['file'] ?? '');
    }

    /**
     * Configure activity logging options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
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
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('expense_sheet_cost')
            ->setDescriptionForEvent(fn (string $eventName) => match ($eventName) {
                'created' => 'Coût de feuille de frais créé',
                'updated' => 'Coût de feuille de frais modifié',
                'deleted' => 'Coût de feuille de frais supprimé',
                default => "Coût de feuille de frais {$eventName}",
            });
    }
}
