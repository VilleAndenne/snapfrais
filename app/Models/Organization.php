<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    protected $fillable = [
        'name',
        'description',
    ];

    public function forms()
    {
        return $this->hasMany(Form::class);
    }
}
