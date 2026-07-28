<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ExpenseSheet extends Model
{
    use BelongsToOrganization, HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'user_id',
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
        'validated_at' => 'datetime',
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
     * Eager loading commun aux listes de validation (dashboard et rappel).
     *
     * Volontairement sans `costs` : le job de rappel ne fait que compter les
     * notes et évaluer la policy. Le dashboard, qui affiche le détail, ajoute
     * `costs` de son côté.
     */
    public function scopeWithValidationRelations(Builder $query): Builder
    {
        return $query->with([
            'form',
            'department.heads',
            'department.parent.heads',
            'user',
        ]);
    }

    /**
     * Scope des notes candidates à la validation par un responsable donné.
     *
     * Sélectionne les notes dont le validateur est responsable du département
     * de la note OU du département parent (N+1). Le filtrage fin
     * (shouldAppearInValidationList) reste appliqué en PHP car il dépend de la policy.
     */
    public function scopePendingValidationBy(Builder $query, User $validator): Builder
    {
        return $query->where(function ($q) use ($validator) {
            $q->whereHas('department.heads', function ($h) use ($validator) {
                $h->where('users.id', $validator->id);
            })
                ->orWhereHas('department.parent.heads', function ($h) use ($validator) {
                    $h->where('users.id', $validator->id);
                });
        });
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
     * Détermine les utilisateurs qui doivent être notifiés pour valider cette note de frais.
     *
     * L'escalade se base sur l'agent bénéficiaire de la note (et non sur l'encodeur),
     * afin de rester cohérente avec la politique de validation (ExpenseSheetPolicy).
     *
     * Règles :
     * - Si le bénéficiaire est responsable de son département et que celui-ci a un parent
     *   → notifier les responsables du parent (escalade N+1).
     * - Si le bénéficiaire est responsable d'un département racine (sans parent_id)
     *   → auto-validation : notifier uniquement le bénéficiaire lui-même.
     * - Sinon → notifier les responsables du département de la note.
     */
    public function resolveApprovers(): Collection
    {
        $department = $this->department;

        if (! $department) {
            return collect();
        }

        $beneficiary = $this->user;
        $heads = $department->heads;

        if ($heads->contains($beneficiary)) {
            if ($department->parent) {
                return $department->parent->heads;
            }

            return collect([$beneficiary]);
        }

        return $heads;
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

    /**
     * Configure activity logging options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'user_id',
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
                'deleted_at',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('expense_sheet')
            ->setDescriptionForEvent(fn (string $eventName) => match ($eventName) {
                'created' => 'Feuille de frais créée',
                'updated' => 'Feuille de frais modifiée',
                'deleted' => 'Feuille de frais supprimée',
                default => "Feuille de frais {$eventName}",
            });
    }
}
