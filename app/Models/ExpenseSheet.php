<?php

namespace App\Models;

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
    ];

    protected $casts = [
        'route' => 'array',
    ];

    public function steps()
    {
        return $this->hasMany(ExpenseSheetStep::class);
    }
}
