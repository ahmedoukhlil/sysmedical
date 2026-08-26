<?php

namespace Tests\Feature;

use App\Http\Livewire\RdvReminders;
use App\Jobs\SendWhatsAppMessage;
use App\Models\Medecin;
use App\Models\Patient;
use App\Models\Rendezvou;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\Feature\Concerns\CreatesTenantFixtures;
use Tests\TestCase;

class RdvRemindersReminderTrackingTest extends TestCase
{
    use DatabaseTransactions;
    use CreatesTenantFixtures;

    public function test_send_reminder_tracks_date_without_altering_rdv_status()
    {
        Queue::fake();

        [$cabinet, $user] = $this->makeCabinetWithUser(15001, 'userReminder1');
        $this->actingAs($user);

        $medecin = Medecin::create(['Nom' => 'Test', 'fkidcabinet' => $cabinet->idEntete]);
        $patient = Patient::create([
            'Nom' => 'PatientReminder1',
            'Telephone1' => '22212345678',
            'fkidcabinet' => $cabinet->idEntete,
        ]);

        $rdv = Rendezvou::create([
            'fkidPatient' => $patient->ID,
            'fkidMedecin' => $medecin->idMedecin,
            'fkidcabinet' => $cabinet->idEntete,
            'dtPrevuRDV' => now()->addDay()->format('Y-m-d'),
            'HeureRdv' => now(),
            'OrdreRDV' => 1,
            'rdvConfirmer' => 'Confirmé',
        ]);

        Livewire::test(RdvReminders::class)->call('sendReminder', $rdv->IDRdv);

        $rdv->refresh();

        $this->assertEquals('Confirmé', $rdv->rdvConfirmer);
        $this->assertNotNull($rdv->date_dernier_rappel);
        Queue::assertPushed(SendWhatsAppMessage::class);
    }
}
