<?php

namespace Tests\Feature;

use App\Models\CabinetSubscription;
use App\Models\Infocabinet;
use App\Models\PlatformAdmin;
use App\Models\SubscriptionPlan;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Feature\Concerns\CreatesTenantFixtures;
use Tests\TestCase;

class SubscriptionTest extends TestCase
{
    use DatabaseTransactions;
    use CreatesTenantFixtures;

    private function makeAdmin(string $email): PlatformAdmin
    {
        return PlatformAdmin::create([
            'name' => 'Admin',
            'email' => $email,
            'password' => 'secret123',
        ]);
    }

    public function test_creating_cabinet_creates_trial_subscription()
    {
        SubscriptionPlan::firstOrCreate(
            ['code' => 'essentiel'],
            ['nom' => 'Essentiel', 'prix_mensuel' => 1500, 'devise' => 'MRU', 'actif' => true, 'ordre' => 1]
        );

        $admin = $this->makeAdmin('admin8001@platform.test');

        $response = $this->actingAs($admin, 'admin')->post(route('admin.cabinets.store'), [
            'nom_cabinet' => 'Cabinet Test 8001',
            'owner_login' => 'owner8001',
            'owner_password' => 'password123',
            'owner_nom' => 'Owner 8001',
        ]);

        $response->assertRedirect(route('admin.cabinets.index'));

        $cabinet = Infocabinet::where('NomCabFr', 'Cabinet Test 8001')->firstOrFail();
        $subscription = CabinetSubscription::where('idEntete', $cabinet->idEntete)->first();

        $this->assertNotNull($subscription);
        $this->assertEquals(CabinetSubscription::STATUT_ESSAI, $subscription->statut);
        $this->assertEquals('essentiel', $subscription->plan->code);
        $this->assertTrue($subscription->trial_ends_at->diffInDays(now()->addDays(7)) <= 1);
    }

    public function test_suspend_command_marks_expired_trial_as_impaye()
    {
        [$cabinet, , $subscription] = $this->makeCabinetWithSubscription(
            8002,
            'user8002',
            'essentiel',
            CabinetSubscription::STATUT_ESSAI,
            now()->subDay()
        );

        $this->artisan('cabinets:suspend-overdue')->assertSuccessful();

        $subscription->refresh();
        $cabinet->refresh();

        $this->assertEquals(CabinetSubscription::STATUT_IMPAYE, $subscription->statut);
        $this->assertNotNull($subscription->grace_ends_at);
        $this->assertEquals('actif', $cabinet->statut);
    }

    public function test_suspend_command_suspends_after_grace_period()
    {
        [$cabinet, , $subscription] = $this->makeCabinetWithSubscription(
            8003,
            'user8003',
            'essentiel',
            CabinetSubscription::STATUT_IMPAYE,
            now()->subDays(10),
            now()->subDay()
        );

        $this->artisan('cabinets:suspend-overdue')->assertSuccessful();

        $subscription->refresh();
        $cabinet->refresh();

        $this->assertEquals(CabinetSubscription::STATUT_SUSPENDU, $subscription->statut);
        $this->assertEquals('suspendu', $cabinet->statut);
    }

    public function test_suspend_command_does_not_touch_active_paid_subscription()
    {
        [$cabinet, , $subscription] = $this->makeCabinetWithSubscription(
            8004,
            'user8004',
            'essentiel',
            CabinetSubscription::STATUT_ACTIF,
            null,
            null,
            now()->addMonth()
        );

        $this->artisan('cabinets:suspend-overdue')->assertSuccessful();

        $subscription->refresh();
        $cabinet->refresh();

        $this->assertEquals(CabinetSubscription::STATUT_ACTIF, $subscription->statut);
        $this->assertEquals('actif', $cabinet->statut);
    }

    public function test_suspend_command_marks_expired_paid_period_as_impaye()
    {
        [, , $subscription] = $this->makeCabinetWithSubscription(
            8005,
            'user8005',
            'essentiel',
            CabinetSubscription::STATUT_ACTIF,
            null,
            null,
            now()->subDay()
        );

        $this->artisan('cabinets:suspend-overdue')->assertSuccessful();

        $subscription->refresh();

        $this->assertEquals(CabinetSubscription::STATUT_IMPAYE, $subscription->statut);
    }

    public function test_recording_manual_payment_extends_period_and_reactivates()
    {
        [$cabinet, , $subscription] = $this->makeCabinetWithSubscription(
            8006,
            'user8006',
            'essentiel',
            CabinetSubscription::STATUT_SUSPENDU,
            now()->subDays(20),
            now()->subDays(5)
        );
        $cabinet->forceFill(['statut' => 'suspendu'])->save();

        $admin = $this->makeAdmin('admin8006@platform.test');

        $response = $this->actingAs($admin, 'admin')->post(
            route('admin.cabinets.subscription.payment', $cabinet->idEntete),
            [
                'montant' => 1500,
                'moyen' => 'especes',
                'date_paiement' => now()->format('Y-m-d'),
                'mois_couverts' => 1,
            ]
        );

        $response->assertRedirect();

        $subscription->refresh();
        $cabinet->refresh();

        $this->assertEquals(CabinetSubscription::STATUT_ACTIF, $subscription->statut);
        $this->assertNull($subscription->grace_ends_at);
        $this->assertTrue($subscription->current_period_ends_at->isFuture());
        $this->assertEquals('actif', $cabinet->statut);

        $payment = $subscription->payments()->first();
        $this->assertNotNull($payment);
        $this->assertEquals($admin->id, $payment->platform_admin_id);
    }

    public function test_change_plan_updates_subscription()
    {
        [$cabinet, , $subscription] = $this->makeCabinetWithSubscription(8007, 'user8007');

        $standardPlan = SubscriptionPlan::firstOrCreate(
            ['code' => 'standard'],
            ['nom' => 'Standard', 'prix_mensuel' => 3000, 'devise' => 'MRU', 'actif' => true, 'ordre' => 2]
        );

        $admin = $this->makeAdmin('admin8007@platform.test');

        $response = $this->actingAs($admin, 'admin')->post(
            route('admin.cabinets.subscription.change-plan', $cabinet->idEntete),
            ['subscription_plan_id' => $standardPlan->id]
        );

        $response->assertRedirect();

        $subscription->refresh();
        $this->assertEquals($standardPlan->id, $subscription->subscription_plan_id);
    }
}
