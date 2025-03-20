<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Form extends Model
{
    protected $fillable = ['name', 'description', 'organization_id'];

    public function costs()
    {
        return $this->hasMany(FormCost::class);
    }

    public function expenseSheets()
    {
        return $this->hasMany(ExpenseSheet::class);
    }
}
