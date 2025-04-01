<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use SoftDeletes;
    protected $fillable = ['name', 'parent_id', 'organization_id'];

    public function users()
    {
        return $this->belongsToMany(User::class);
    }
}
