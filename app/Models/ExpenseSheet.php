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
     *
     * Un responsable peut voir :
     * - Ses propres notes
     * - Les notes de son département ET de tous les sous-départements (hiérarchie complète)
     * - Sauf celles où l'auteur est co-responsable du même département que lui
     */
    public function scopeVisibleBy(Builder $query, User $user): Builder
    {
        // Admin peut tout voir
        if ($user->is_admin) {
            return $query;
        }

        // Récupérer tous les départements où l'utilisateur est responsable
        $headDepartmentIds = $user->headOfDepartments()->pluck('departments.id')->toArray();

        // Récupérer également tous les sous-départements (récursif)
        $allVisibleDepartmentIds = $this->getAllDescendantDepartmentIds($headDepartmentIds);

        return $query->where(function ($q) use ($user, $headDepartmentIds, $allVisibleDepartmentIds) {
            // L'utilisateur peut voir ses propres notes
            $q->where('expense_sheets.user_id', $user->id);

            // Ou les notes des départements où il est responsable + tous les sous-départements
            if (! empty($allVisibleDepartmentIds)) {
                $q->orWhere(function ($subQ) use ($headDepartmentIds, $allVisibleDepartmentIds) {
                    $subQ->whereIn('expense_sheets.department_id', $allVisibleDepartmentIds)
                        // Mais pas celles où l'auteur est co-responsable du même département que l'utilisateur
                        ->whereNotExists(function ($existsQ) use ($headDepartmentIds) {
                            $existsQ->select(\DB::raw(1))
                                ->from('department_user')
                                ->whereColumn('department_user.user_id', 'expense_sheets.user_id')
                                ->whereIn('department_user.department_id', $headDepartmentIds)
                                ->where('department_user.is_head', 1);
                        });
                });
            }
        });
    }

    /**
     * Récupère récursivement tous les IDs des départements descendants.
     *
     * @param  array  $parentIds  IDs des départements parents
     * @return array IDs de tous les départements (parents + descendants)
     */
    private function getAllDescendantDepartmentIds(array $parentIds): array
    {
        if (empty($parentIds)) {
            return [];
        }

        $allIds = $parentIds;
        $currentLevelIds = $parentIds;

        // Parcourir la hiérarchie niveau par niveau
        while (! empty($currentLevelIds)) {
            $childIds = Department::whereIn('parent_id', $currentLevelIds)
                ->pluck('id')
                ->toArray();

            if (empty($childIds)) {
                break;
            }

            $allIds = array_merge($allIds, $childIds);
            $currentLevelIds = $childIds;
        }

        return array_unique($allIds);
    }
}
