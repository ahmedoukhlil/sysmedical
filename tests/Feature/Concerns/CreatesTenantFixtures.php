<?php

namespace Tests\Feature\Concerns;

use App\Models\CabinetSubscription;
use App\Models\Infocabinet;
use App\Models\SubscriptionPlan;
use App\Models\TUser;
use Carbon\Carbon;

trait CreatesTenantFixtures
{
    private function makeCabinetWithUser(int $idEntete, string $login): array
    {
        $cabinet = new Infocabinet();
        $cabinet->forceFill([
            'idEntete' => $idEntete,
            'NomCabFr' => "Cabinet $idEntete",
        ])->save();

        $user = TUser::create([
            'login' => $login,
            'password' => 'secret',
            'NomComplet' => "User $login",
            'IdClasseUser' => 1,
            'fkidcabinet' => $idEntete,
            'ismasquer' => 0,
        ]);

        return [$cabinet, $user];
    }

    private function makeCabinetWithSubscription(
        int $idEntete,
        string $login,
        string $planCode = 'essentiel',
        string $statut = CabinetSubscription::STATUT_ESSAI,
        ?Carbon $trialEndsAt = null,
        ?Carbon $graceEndsAt = null,
        ?Carbon $periodEndsAt = null
    ): array {
        [$cabinet, $user] = $this->makeCabinetWithUser($idEntete, $login);

        $plan = SubscriptionPlan::firstOrCreate(
            ['code' => $planCode],
            [
                'nom' => ucfirst($planCode),
                'prix_mensuel' => 1500,
                'devise' => 'MRU',
                'actif' => true,
                'ordre' => 1,
            ]
        );

        $subscription = new CabinetSubscription();
        $subscription->forceFill([
            'idEntete' => $idEntete,
            'subscription_plan_id' => $plan->id,
            'statut' => $statut,
            'trial_ends_at' => $trialEndsAt,
            'grace_ends_at' => $graceEndsAt,
            'current_period_ends_at' => $periodEndsAt,
        ])->save();

        return [$cabinet, $user, $subscription];
    }
}
