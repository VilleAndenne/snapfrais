<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class OrganizationScope implements Scope
{
    /**
     * Constrain the query to the organization resolved for the current request.
     *
     * When no organization is bound (console, queue, or cross-tenant contexts),
     * the scope is a no-op so the model behaves as usual.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $organization = currentOrganization();

        if ($organization !== null) {
            $builder->where($model->qualifyColumn('organization_id'), $organization->getKey());
        }
    }
}
