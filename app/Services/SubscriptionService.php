<?php

namespace App\Services;

use App\Models\CabinetSubscription;
use App\Models\Infocabinet;
use App\Models\PlatformAdmin;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use Carbon\Carbon;

class SubscriptionService
{
    public function createTrialSubscription(Infocabinet $cabinet, SubscriptionPlan $plan, int $trialDays = 7): CabinetSubscription
    {
        return CabinetSubscription::create([
            'idEntete' => $cabinet->idEntete,
            'subscription_plan_id' => $plan->id,
            'statut' => CabinetSubscription::STATUT_ESSAI,
            'trial_ends_at' => now()->addDays($trialDays),
        ]);
    }

    public function recordManualPayment(CabinetSubscription $subscription, array $data, PlatformAdmin $admin): SubscriptionPayment
    {
        $payment = SubscriptionPayment::create([
            'cabinet_subscription_id' => $subscription->id,
            'montant' => $data['montant'],
            'devise' => $data['devise'] ?? 'MRU',
            'moyen' => $data['moyen'],
            'date_paiement' => $data['date_paiement'],
            'mois_couverts' => $data['mois_couverts'] ?? 1,
            'note' => $data['note'] ?? null,
            'platform_admin_id' => $admin->id,
        ]);

        $base = collect([
            now(),
            $subscription->current_period_ends_at,
            $subscription->trial_ends_at,
        ])->filter()->max();

        $subscription->current_period_ends_at = Carbon::parse($base)->addMonths($payment->mois_couverts);
        $subscription->statut = CabinetSubscription::STATUT_ACTIF;
        $subscription->grace_ends_at = null;
        $subscription->suspended_at = null;
        $subscription->save();

        $cabinet = $subscription->cabinet;
        if ($cabinet && $cabinet->statut === 'suspendu') {
            $cabinet->forceFill(['statut' => 'actif'])->save();
        }

        return $payment;
    }

    public function changePlan(CabinetSubscription $subscription, SubscriptionPlan $plan): void
    {
        $subscription->subscription_plan_id = $plan->id;
        $subscription->save();
    }

    public function markOverdueIfExpired(CabinetSubscription $subscription, int $graceDays = 5): bool
    {
        $expired = ($subscription->statut === CabinetSubscription::STATUT_ESSAI && $subscription->trial_ends_at && $subscription->trial_ends_at->isPast())
            || ($subscription->statut === CabinetSubscription::STATUT_ACTIF && $subscription->current_period_ends_at && $subscription->current_period_ends_at->isPast());

        if (!$expired) {
            return false;
        }

        $subscription->statut = CabinetSubscription::STATUT_IMPAYE;
        $subscription->grace_ends_at = now()->addDays($graceDays);
        $subscription->save();

        return true;
    }

    public function suspendIfGraceExpired(CabinetSubscription $subscription): bool
    {
        if ($subscription->statut !== CabinetSubscription::STATUT_IMPAYE) {
            return false;
        }

        if (!$subscription->grace_ends_at || $subscription->grace_ends_at->isFuture()) {
            return false;
        }

        $subscription->statut = CabinetSubscription::STATUT_SUSPENDU;
        $subscription->suspended_at = now();
        $subscription->save();

        $cabinet = $subscription->cabinet;
        if ($cabinet) {
            $cabinet->forceFill(['statut' => 'suspendu'])->save();
        }

        return true;
    }

    public function processAllOverdue(): array
    {
        $graceDays = config('subscription.grace_days', 5);

        $passesImpaye = 0;
        $suspendus = 0;

        CabinetSubscription::whereNotIn('statut', [
            CabinetSubscription::STATUT_RESILIE,
            CabinetSubscription::STATUT_SUSPENDU,
        ])->each(function (CabinetSubscription $subscription) use ($graceDays, &$passesImpaye, &$suspendus) {
            if ($this->markOverdueIfExpired($subscription, $graceDays)) {
                $passesImpaye++;
            }

            if ($this->suspendIfGraceExpired($subscription)) {
                $suspendus++;
            }
        });

        return ['passes_impaye' => $passesImpaye, 'suspendus' => $suspendus];
    }
}
