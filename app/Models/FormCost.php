<?php

namespace App\Models;

use App\Casts\CostCast;
use Illuminate\Database\Eloquent\Model;

class FormCost extends Model
{
    protected $fillable = ['name', 'description', 'type', 'processing_department'];

    protected $casts = [
        'cost' => CostCast::class,
    ];

    public function form()
    {
        return $this->belongsTo(Form::class);
    }

    public function reimbursementRates()
    {
        return $this->hasMany(FormCostRemboursiementRate::class);
    }

    public function requirements()
    {
        return $this->hasMany(FormCostRequirement::class);
    }
}
