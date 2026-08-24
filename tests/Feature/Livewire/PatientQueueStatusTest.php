<?php

namespace Tests\Feature\Livewire;

use App\Http\Controllers\PatientInterfaceController;
use App\Http\Livewire\PatientQueueStatus;
use App\Models\Patient;
use App\Models\Rendezvou;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\Feature\Concerns\CreatesTenantFixtures;
use Tests\TestCase;

class PatientQueueStatusTest extends TestCase
{
    use DatabaseTransactions;
    use CreatesTenantFixtures;

    public function test_component_renders_with_poll_directive()
    {
        [$cabinet, $user] = $this->makeCabinetWithUser(12001, 'userQueue1');
        $this->actingAs($user);

        $patient = Patient::create(['Nom' => 'PatientQueue1', 'fkidcabinet' => $cabinet->idEntete]);
        $token = PatientInterfaceController::generateToken($patient->ID);

        $component = Livewire::test(PatientQueueStatus::class, ['token' => $token]);

        $component->assertSee('wire:poll', false);
    }

    public function test_component_shows_correct_position_for_todays_appointment()
    {
        [$cabinet, $user] = $this->makeCabinetWithUser(12002, 'userQueue2');
        $this->actingAs($user);

        $patient = Patient::create(['Nom' => 'PatientQueue2', 'fkidcabinet' => $cabinet->idEntete]);

        $rdv = Rendezvou::create([
            'fkidPatient' => $patient->ID,
            'fkidMedecin' => 1,
            'fkidcabinet' => $cabinet->idEntete,
            'dtPrevuRDV' => now()->format('Y-m-d'),
            'HeureRdv' => now(),
            'OrdreRDV' => 3,
            'rdvConfirmer' => 'En Attente',
        ]);

        $token = PatientInterfaceController::generateToken($patient->ID, now()->format('Y-m-d'));

        $component = Livewire::test(PatientQueueStatus::class, ['token' => $token]);

        $this->assertTrue($component->get('estAujourdhui'));
        $this->assertEquals(3, $component->get('positionPatient'));
        $this->assertCount(1, $component->get('rendezVousMedecinJournee'));
    }

    public function test_invalid_token_does_not_throw()
    {
        $component = Livewire::test(PatientQueueStatus::class, ['token' => 'invalid-token']);

        $this->assertNull($component->get('patient'));
        $this->assertNull($component->get('prochainRdv'));
    }
}
