<?php

namespace App\Models;

use App\Casts\CostCast;
use Illuminate\Database\Eloquent\Model;

class ExpenseSheet extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'distance',
        'route',
        'total',
        'status',
        'form_id',
        'department_id',
        'validated_by',
        'validated_at',
        'approved',
        'refusal_reason',
    ];

    // Add global scopes to the model
    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope('canView', function ($query) {
            $user = auth()->user();
            $query->where(function ($q) use ($user) {
                $q->whereHas('department', function ($q) use ($user) {
                    $q->where('manager_id', $user->id);
                })->orWhere('user_id', $user->id);
            });
        });
    }

    protected $casts = [
        'route' => 'array',
    ];


    public function costs()
    {
        return $this->hasMany(ExpenseSheetCost::class);
    }

    public function form()
    {
        return $this->belongsTo(Form::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function validatedBy()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }
}
