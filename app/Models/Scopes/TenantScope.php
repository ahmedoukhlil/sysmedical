<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        if (App::runningInConsole() && !App::runningUnitTests()) {
            return;
        }

        if (!Auth::check()) {
            return;
        }

        $column = $model->getTenantColumn();

        $builder->where($model->getTable() . '.' . $column, Auth::user()->fkidcabinet);
    }
}
