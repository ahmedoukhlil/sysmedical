<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

trait BelongsToTenantViaRelation
{
    public static function bootBelongsToTenantViaRelation()
    {
        static::addGlobalScope('tenant_via_relation', function (Builder $builder) {
            if (App::runningInConsole() && !App::runningUnitTests()) {
                return;
            }

            if (!Auth::check()) {
                return;
            }

            $relation = static::$tenantRelation;
            $column = static::$tenantRelationColumn ?? 'fkidCabinet';

            $builder->whereHas($relation, function ($q) use ($column) {
                $q->where($column, Auth::user()->fkidcabinet);
            });
        });
    }

    public function scopeWithoutTenant($query)
    {
        return $query->withoutGlobalScope('tenant_via_relation');
    }
}
