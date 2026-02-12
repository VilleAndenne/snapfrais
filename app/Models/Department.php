<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Department extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = ['name', 'parent_id'];

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function heads()
    {
        return $this->belongsToMany(User::class, 'department_user', 'department_id', 'user_id')->wherePivot('is_head', true);
    }

    public function parent()
    {
        return $this->belongsTo(Department::class, 'parent_id');
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
            ->logOnly(['name', 'parent_id', 'deleted_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('department')
            ->setDescriptionForEvent(fn (string $eventName) => match ($eventName) {
                'created' => 'Département créé',
                'updated' => 'Département modifié',
                'deleted' => 'Département supprimé',
                default => "Département {$eventName}",
            });
    }
}
