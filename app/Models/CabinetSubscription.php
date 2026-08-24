<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CabinetSubscription extends Model
{
    const STATUT_ESSAI = 'essai';
    const STATUT_ACTIF = 'actif';
    const STATUT_IMPAYE = 'impaye';
    const STATUT_SUSPENDU = 'suspendu';
    const STATUT_RESILIE = 'resilie';

    protected $fillable = [
        'idEntete',
        'subscription_plan_id',
        'statut',
        'trial_ends_at',
        'grace_ends_at',
        'current_period_ends_at',
        'suspended_at',
    ];

    protected $casts = [
        'idEntete' => 'int',
        'subscription_plan_id' => 'int',
        'trial_ends_at' => 'datetime',
        'grace_ends_at' => 'datetime',
        'current_period_ends_at' => 'datetime',
        'suspended_at' => 'datetime',
    ];

    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function cabinet()
    {
        return $this->belongsTo(Infocabinet::class, 'idEntete', 'idEntete');
    }

    public function payments()
    {
        return $this->hasMany(SubscriptionPayment::class);
    }

    public function isEnEssai()
    {
        return $this->statut === self::STATUT_ESSAI;
    }

    public function estExpire()
    {
        if ($this->statut === self::STATUT_ESSAI) {
            return $this->trial_ends_at && $this->trial_ends_at->isPast();
        }

        if ($this->statut === self::STATUT_ACTIF) {
            return $this->current_period_ends_at && $this->current_period_ends_at->isPast();
        }

        return false;
    }
}
