<?php

namespace Tests\Feature;

use App\Http\Livewire\AgendaSemaine;
use App\Models\Medecin;
use App\Models\Patient;
use App\Models\Rendezvou;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\Feature\Concerns\CreatesTenantFixtures;
use Tests\TestCase;

class AgendaSemaineTest extends TestCase
{
    use DatabaseTransactions;
    use CreatesTenantFixtures;

    public function test_current_week_is_displayed_on_mount()
    {
        [$cabinet, $user] = $this->makeCabinetWithUser(18001, 'userAgenda1');
        $this->actingAs($user);

        Livewire::test(AgendaSemaine::class)
            ->assertSet('semaineDebut', now()->startOfWeek()->format('Y-m-d'));
    }

    public function test_navigation_moves_week_by_seven_days()
    {
        [$cabinet, $user] = $this->makeCabinetWithUser(18002, 'userAgenda2');
        $this->actingAs($user);

        $debut = now()->startOfWeek()->format('Y-m-d');

        $component = Livewire::test(AgendaSemaine::class)
            ->call('semaineSuivante')
            ->assertSet('semaineDebut', now()->startOfWeek()->addWeek()->format('Y-m-d'))
            ->call('semainePrecedente')
            ->assertSet('semaineDebut', $debut)
            ->call('semainePrecedente')
            ->assertSet('semaineDebut', now()->startOfWeek()->subWeek()->format('Y-m-d'))
            ->call('semaineActuelle')
            ->assertSet('semaineDebut', $debut);
    }

    public function test_non_owner_doctor_only_sees_own_rdvs()
    {
        [$cabinet, $secretaire] = $this->makeCabinetWithUser(18003, 'userAgenda3');

        $medecinA = Medecin::create(['Nom' => 'Dr A', 'fkidcabinet' => $cabinet->idEntete]);
        $medecinB = Medecin::create(['Nom' => 'Dr B', 'fkidcabinet' => $cabinet->idEntete]);

        $docteur = \App\Models\TUser::create([
            'login' => 'docteurAgenda3',
            'password' => 'secret',
            'NomComplet' => 'Docteur A',
            'IdClasseUser' => 2,
            'fkidcabinet' => $cabinet->idEntete,
            'fkidmedecin' => $medecinA->idMedecin,
            'ismasquer' => 0,
        ]);

        $patient = Patient::create(['Nom' => 'PatientAgenda3', 'fkidcabinet' => $cabinet->idEntete]);
        $date = now()->startOfWeek()->format('Y-m-d');

        Rendezvou::create([
            'fkidPatient' => $patient->ID,
            'fkidMedecin' => $medecinA->idMedecin,
            'fkidcabinet' => $cabinet->idEntete,
            'dtPrevuRDV' => $date,
            'HeureRdv' => '08:00',
            'OrdreRDV' => 1,
            'rdvConfirmer' => 'En Attente',
        ]);
        Rendezvou::create([
            'fkidPatient' => $patient->ID,
            'fkidMedecin' => $medecinB->idMedecin,
            'fkidcabinet' => $cabinet->idEntete,
            'dtPrevuRDV' => $date,
            'HeureRdv' => '09:00',
            'OrdreRDV' => 1,
            'rdvConfirmer' => 'En Attente',
        ]);

        $this->actingAs($docteur);

        $component = Livewire::test(AgendaSemaine::class);
        $component->assertSet('medecinFiltre', $medecinA->idMedecin);

        $jours = $component->get('joursSemaine');
        $totalRdvs = $jours->sum(fn ($jour) => $jour['rdvs']->count());
        $this->assertEquals(1, $totalRdvs);
    }

    public function test_annule_excluded_but_termine_included()
    {
        [$cabinet, $user] = $this->makeCabinetWithUser(18004, 'userAgenda4');
        $this->actingAs($user);

        $medecin = Medecin::create(['Nom' => 'DrTest', 'fkidcabinet' => $cabinet->idEntete]);
        $patient = Patient::create(['Nom' => 'PatientAgenda4', 'fkidcabinet' => $cabinet->idEntete]);
        $date = now()->startOfWeek()->format('Y-m-d');

        Rendezvou::create([
            'fkidPatient' => $patient->ID,
            'fkidMedecin' => $medecin->idMedecin,
            'fkidcabinet' => $cabinet->idEntete,
            'dtPrevuRDV' => $date,
            'HeureRdv' => '08:00',
            'OrdreRDV' => 1,
            'rdvConfirmer' => 'Annulé',
        ]);
        Rendezvou::create([
            'fkidPatient' => $patient->ID,
            'fkidMedecin' => $medecin->idMedecin,
            'fkidcabinet' => $cabinet->idEntete,
            'dtPrevuRDV' => $date,
            'HeureRdv' => '09:00',
            'OrdreRDV' => 2,
            'rdvConfirmer' => 'Terminé',
        ]);

        $component = Livewire::test(AgendaSemaine::class);
        $jours = $component->get('joursSemaine');
        $totalRdvs = $jours->sum(fn ($jour) => $jour['rdvs']->count());

        $this->assertEquals(1, $totalRdvs);
    }

    public function test_demarrer_and_terminer_rdv()
    {
        [$cabinet, $user] = $this->makeCabinetWithUser(18005, 'userAgenda5');
        $this->actingAs($user);

        $medecin = Medecin::create(['Nom' => 'Dr Test5', 'fkidcabinet' => $cabinet->idEntete]);
        $patient = Patient::create(['Nom' => 'PatientAgenda5', 'fkidcabinet' => $cabinet->idEntete]);
        $date = now()->startOfWeek()->format('Y-m-d');

        $rdv = Rendezvou::create([
            'fkidPatient' => $patient->ID,
            'fkidMedecin' => $medecin->idMedecin,
            'fkidcabinet' => $cabinet->idEntete,
            'dtPrevuRDV' => $date,
            'HeureRdv' => '08:00',
            'OrdreRDV' => 1,
            'rdvConfirmer' => 'En Attente',
        ]);

        Livewire::test(AgendaSemaine::class)->call('demarrerRdv', $rdv->IDRdv);
        $rdv->refresh();
        $this->assertEquals('En cours', $rdv->rdvConfirmer);

        Livewire::test(AgendaSemaine::class)->call('terminerRdv', $rdv->IDRdv);
        $rdv->refresh();
        $this->assertEquals('Terminé', $rdv->rdvConfirmer);
    }
}
