<?php

namespace App\Models;

use App\Casts\CostCast;
use Illuminate\Database\Eloquent\Model;

class FormCostRemboursiementRate extends Model
{
    protected $fillable = [
        'cost_id',
        'start_date',
        'end_date',
        'value',
        'transport'
    ];
}
