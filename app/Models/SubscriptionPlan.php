<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    protected $fillable = [
        'code',
        'nom',
        'prix_mensuel',
        'devise',
        'description',
        'fonctionnalites',
        'max_users',
        'max_storage_mb',
        'actif',
        'ordre',
    ];

    protected $casts = [
        'fonctionnalites' => 'array',
        'actif' => 'bool',
    ];

    public function subscriptions()
    {
        return $this->hasMany(CabinetSubscription::class);
    }
}
