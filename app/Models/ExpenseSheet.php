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
    ];

    protected $casts = [
        'route' => 'array',
        'total' => CostCast::class,
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
}
