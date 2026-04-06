<?php

namespace Relaticle\Comments\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Relaticle\Comments\CommentsConfig;

class TenantScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * This is called automatically by Laravel every time a query is executed
     * against any model that has registered this scope via addGlobalScope().
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (! CommentsConfig::isMultiTenancyEnabled()) {
            return;
        }

        $tenantId = CommentsConfig::resolveTenantId();

        if ($tenantId === null) {
            return;
        }

        $column = $model->qualifyColumn(CommentsConfig::getTenantColumn());

        $builder->where($column, $tenantId);
    }
}
