<?php

namespace App\Models\Concerns;

use App\Models\Scopes\TenantScope;
use Illuminate\Support\Facades\Auth;

trait BelongsToTenant
{
    public static function bootBelongsToTenant()
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function ($model) {
            $column = $model->getTenantColumn();

            if (empty($model->{$column}) && Auth::check()) {
                $model->{$column} = Auth::user()->fkidcabinet;
            }
        });
    }

    public function getTenantColumn()
    {
        return static::$tenantColumn ?? 'fkidCabinet';
    }

    public function scopeWithoutTenant($query)
    {
        return $query->withoutGlobalScope(TenantScope::class);
    }
}
