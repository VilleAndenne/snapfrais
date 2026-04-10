<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Form extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = ['name', 'description', 'organization_id'];

    public function costs()
    {
        return $this->hasMany(FormCost::class);
    }

    public function expenseSheets()
    {
        return $this->hasMany(ExpenseSheet::class);
    }

    /**
     * Configure activity logging options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'description', 'organization_id', 'deleted_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('form')
            ->setDescriptionForEvent(fn (string $eventName) => match ($eventName) {
                'created' => 'Formulaire créé',
                'updated' => 'Formulaire modifié',
                'deleted' => 'Formulaire supprimé',
                default => "Formulaire {$eventName}",
            });
    }
}
