<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormRequirement extends Model
{
    protected $fillable = [
        'form_id',
        'name',
        'description',
        'is_required',
        'type',
    ];

    public function form()
    {
        return $this->belongsTo(Form::class);
    }
}
