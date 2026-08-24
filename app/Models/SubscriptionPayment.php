<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPayment extends Model
{
    protected $fillable = [
        'cabinet_subscription_id',
        'montant',
        'devise',
        'moyen',
        'date_paiement',
        'mois_couverts',
        'note',
        'platform_admin_id',
    ];

    protected $casts = [
        'montant' => 'int',
        'date_paiement' => 'date',
        'mois_couverts' => 'int',
    ];

    public function subscription()
    {
        return $this->belongsTo(CabinetSubscription::class, 'cabinet_subscription_id');
    }

    public function admin()
    {
        return $this->belongsTo(PlatformAdmin::class, 'platform_admin_id');
    }
}
