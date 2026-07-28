<?php

namespace App\Models;

use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Organization extends Model
{
    /** @use HasFactory<OrganizationFactory> */
    use HasFactory, LogsActivity;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'organization_name',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function forms(): HasMany
    {
        return $this->hasMany(Form::class);
    }

    public function expenseSheets(): HasMany
    {
        return $this->hasMany(ExpenseSheet::class);
    }

    /**
     * Configure activity logging options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'slug', 'organization_name'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('organization')
            ->setDescriptionForEvent(fn (string $eventName) => match ($eventName) {
                'created' => 'Organisation créée',
                'updated' => 'Organisation modifiée',
                'deleted' => 'Organisation supprimée',
                default => "Organisation {$eventName}",
            });
    }
}
