<?php

namespace App\Models\Concerns;

use App\Models\Scopes\TenantScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;

trait BelongsToTenant
{
    public static function bootBelongsToTenant()
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function ($model) {
            $column = $model->getTenantColumn();

            // Garde-fou de développement : si $tenantColumn n'est pas déclaré
            // explicitement et que la colonne par défaut ('fkidCabinet')
            // n'existe pas réellement sur la table, échoue bruyamment plutôt
            // que de remplir silencieusement un attribut fantôme qui ne serait
            // jamais persisté ni filtré par TenantScope. Coûte une requête
            // Schema::hasColumn (mise en cache par Doctrine/DBAL), désactivé
            // en production pour ne pas payer ce coût à chaque création.
            if (!isset(static::$tenantColumn) && !App::environment('production') && !Schema::hasColumn($model->getTable(), $column)) {
                throw new \RuntimeException(
                    "Le modèle " . static::class . " utilise BelongsToTenant sans déclarer \$tenantColumn, " .
                    "et la colonne par défaut '{$column}' n'existe pas sur la table '{$model->getTable()}'. " .
                    "Déclarez explicitement `protected static \$tenantColumn = '...';` avec le nom réel de la colonne."
                );
            }

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
