<?php

namespace App\Models;

use App\Casts\CostCast;
use Illuminate\Database\Eloquent\Builder;
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
