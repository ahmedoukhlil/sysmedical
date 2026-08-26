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

        // Le modèle utilisateur du guard ne peut pas être filtré par son propre
        // cabinet pour se résoudre lui-même : Auth::user() interroge ce modèle,
        // qui réappliquerait ce scope, qui rappellerait Auth::user()... boucle
        // infinie jusqu'à épuisement mémoire. Le guard résout l'utilisateur non
        // scopé ; les relations/requêtes explicites depuis ce modèle restent
        // scopées normalement par leurs propres modèles cibles.
        if ($model instanceof \Illuminate\Contracts\Auth\Authenticatable) {
            return;
        }

        if (!Auth::check()) {
            return;
        }

        $column = $model->getTenantColumn();

        $builder->where($model->getTable() . '.' . $column, Auth::user()->fkidcabinet);
    }
}
