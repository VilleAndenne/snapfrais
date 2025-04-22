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

    public function heads()
    {
        return $this->belongsToMany(User::class, 'department_user', 'department_id', 'user_id')->wherePivot('is_head', true);
    }

    public function parent()
    {
        return $this->belongsTo(Department::class, 'parent_id');
    }

    public function expenseSheets()
    {
        return $this->hasMany(ExpenseSheet::class);
    }
}
