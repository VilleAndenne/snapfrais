<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpenseSheet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'distance',
        'route',
        'total',
        'status',
        'form_id',
        'department_id',
        'validated_by',
        'validated_at',
        'approved',
        'refusal_reason',
        'created_by',
        'is_draft',
    ];

    protected $casts = [
        'route' => 'array',
        'is_draft' => 'boolean',
    ];

    protected $appends = [
        'steps',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

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

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function validatedBy()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function getStepsAttribute()
    {
        return collect($this->route)->pluck('steps')->flatten();
    }

    public function expenseSheetCosts()
    {
        return $this->hasMany(ExpenseSheetCost::class);
    }

    /**
     * Scope pour filtrer les notes de frais visibles par l'utilisateur.
     */
    public function scopeVisibleBy(Builder $query, User $user): Builder
    {
        // Admin peut tout voir
        if ($user->is_admin) {
            return $query;
        }

        // Récupérer tous les départements où l'utilisateur est responsable
        $headDepartmentIds = $user->headOfDepartments()->pluck('departments.id')->toArray();

        return $query->where(function ($q) use ($user, $headDepartmentIds) {
            // L'utilisateur peut voir ses propres notes
            $q->where('expense_sheets.user_id', $user->id);

            // Ou les notes des départements où il est responsable
            if (! empty($headDepartmentIds)) {
                $q->orWhere(function ($subQ) use ($headDepartmentIds) {
                    $subQ->whereIn('expense_sheets.department_id', $headDepartmentIds)
                        // Mais pas celles où l'auteur est aussi responsable du même département
                        ->whereNotExists(function ($existsQ) use ($headDepartmentIds) {
                            $existsQ->select(\DB::raw(1))
                                ->from('department_head')
                                ->whereColumn('department_head.user_id', 'expense_sheets.user_id')
                                ->whereIn('department_head.department_id', $headDepartmentIds);
                        });
                });
            }
        });
    }
}
