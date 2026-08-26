<?php

namespace Tests\Feature;

use App\Exceptions\RdvConflictException;
use App\Http\Livewire\PatientBookingCalendar;
use App\Jobs\SendWhatsAppMessage;
use App\Models\Medecin;
use App\Models\Patient;
use App\Models\Rendezvou;
use App\Services\PatientTokenService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\Feature\Concerns\CreatesTenantFixtures;
use Tests\TestCase;

class PatientBookingCalendarTest extends TestCase
{
    use DatabaseTransactions;
    use CreatesTenantFixtures;

    public function test_booking_a_free_slot_creates_rdv_en_attente()
    {
        Queue::fake();

        [$cabinet] = $this->makeCabinetWithUser(17001, 'userBooking1');
        $medecin = Medecin::create(['Nom' => 'Dr Test', 'fkidcabinet' => $cabinet->idEntete]);
        $patient = Patient::create(['Nom' => 'PatientBooking1', 'Telephone1' => '22277777777', 'fkidcabinet' => $cabinet->idEntete]);

        $ticket = app(PatientTokenService::class)->generateBookingTicket($patient->ID, $cabinet->idEntete);
        $date = now()->addDay()->format('Y-m-d');

        $component = Livewire::test(PatientBookingCalendar::class, ['ticket' => $ticket])
            ->set('medecinId', $medecin->idMedecin)
            ->set('date', $date)
            ->call('loadCreneaux')
            ->set('heureChoisie', '08:00')
            ->call('confirmerRdv');

        $component->assertSet('confirme', true);

        $this->assertDatabaseHas('rendezvous', [
            'fkidPatient' => $patient->ID,
            'fkidMedecin' => $medecin->idMedecin,
            'rdvConfirmer' => 'En Attente',
        ]);

        Queue::assertPushed(SendWhatsAppMessage::class);
    }

    public function test_booking_an_already_taken_slot_shows_conflict_message()
    {
        [$cabinet] = $this->makeCabinetWithUser(17002, 'userBooking2');
        $medecin = Medecin::create(['Nom' => 'Dr Test2', 'fkidcabinet' => $cabinet->idEntete]);
        $patient = Patient::create(['Nom' => 'PatientBooking2', 'Telephone1' => '22288888888', 'fkidcabinet' => $cabinet->idEntete]);

        $date = now()->addDay()->format('Y-m-d');

        Rendezvou::create([
            'fkidPatient' => $patient->ID,
            'fkidMedecin' => $medecin->idMedecin,
            'fkidcabinet' => $cabinet->idEntete,
            'dtPrevuRDV' => $date,
            'HeureRdv' => '08:00',
            'OrdreRDV' => 1,
            'rdvConfirmer' => 'En Attente',
        ]);

        $ticket = app(PatientTokenService::class)->generateBookingTicket($patient->ID, $cabinet->idEntete);

        $component = Livewire::test(PatientBookingCalendar::class, ['ticket' => $ticket])
            ->set('medecinId', $medecin->idMedecin)
            ->set('date', $date)
            ->set('heureChoisie', '08:00')
            ->call('confirmerRdv');

        $component->assertSet('confirme', false);
        $this->assertNotEmpty($component->get('errorMessage'));
    }

    public function test_concurrent_booking_only_one_succeeds()
    {
        [$cabinet] = $this->makeCabinetWithUser(17003, 'userBooking3');
        $medecin = Medecin::create(['Nom' => 'Dr Test3', 'fkidcabinet' => $cabinet->idEntete]);
        $patientA = Patient::create(['Nom' => 'PatientA', 'Telephone1' => '22201010101', 'fkidcabinet' => $cabinet->idEntete]);
        $patientB = Patient::create(['Nom' => 'PatientB', 'Telephone1' => '22202020202', 'fkidcabinet' => $cabinet->idEntete]);

        $date = now()->addDay()->format('Y-m-d');

        $data = [
            'fkidMedecin' => $medecin->idMedecin,
            'dtPrevuRDV' => $date,
            'HeureRdv' => '09:00',
            'ActePrevu' => 'Consultation',
            'rdvConfirmer' => 'En Attente',
        ];

        $created = Rendezvou::createWithLock($data + ['fkidPatient' => $patientA->ID], $cabinet->idEntete);
        $this->assertNotNull($created);

        $this->expectException(RdvConflictException::class);
        Rendezvou::createWithLock($data + ['fkidPatient' => $patientB->ID], $cabinet->idEntete);
    }

    public function test_upcoming_rdv_quota_blocks_new_booking()
    {
        [$cabinet] = $this->makeCabinetWithUser(17004, 'userBooking4');
        $medecin = Medecin::create(['Nom' => 'Dr Test4', 'fkidcabinet' => $cabinet->idEntete]);
        $patient = Patient::create(['Nom' => 'PatientBooking4', 'Telephone1' => '22203030303', 'fkidcabinet' => $cabinet->idEntete]);

        config(['booking.max_upcoming_rdv_per_patient' => 1]);

        Rendezvou::create([
            'fkidPatient' => $patient->ID,
            'fkidMedecin' => $medecin->idMedecin,
            'fkidcabinet' => $cabinet->idEntete,
            'dtPrevuRDV' => now()->addDays(2)->format('Y-m-d'),
            'HeureRdv' => '08:00',
            'OrdreRDV' => 1,
            'rdvConfirmer' => 'En Attente',
        ]);

        $ticket = app(PatientTokenService::class)->generateBookingTicket($patient->ID, $cabinet->idEntete);
        $date = now()->addDays(3)->format('Y-m-d');

        $component = Livewire::test(PatientBookingCalendar::class, ['ticket' => $ticket])
            ->set('medecinId', $medecin->idMedecin)
            ->set('date', $date)
            ->set('heureChoisie', '09:00')
            ->call('confirmerRdv');

        $component->assertSet('confirme', false);
        $this->assertStringContainsString('maximum', $component->get('errorMessage'));
    }
}
