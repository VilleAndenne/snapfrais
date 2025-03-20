<?php

namespace App\Models;

use App\Casts\CostCast;
use Illuminate\Database\Eloquent\Model;

class FormCost extends Model
{
    protected $fillable = ['name', 'description', 'type'];

    protected $casts = [
        'cost' => CostCast::class,
    ];

    public function form()
    {
        return $this->belongsTo(Form::class);
    }
}
