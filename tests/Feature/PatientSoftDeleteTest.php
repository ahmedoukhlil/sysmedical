<?php

namespace Tests\Feature;

use App\Models\AnalysePatient;
use App\Models\ConsultationMedicale;
use App\Models\DossierMedical;
use App\Models\Facture;
use App\Models\Ordonnance;
use App\Models\Ordonnanceref;
use App\Models\Patient;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use App\Http\Livewire\DossierMedicalManager;
use App\Http\Livewire\OrdonnanceManager;
use App\Http\Livewire\PatientManager;
use Tests\Feature\Concerns\CreatesTenantFixtures;
use Tests\TestCase;

class PatientSoftDeleteTest extends TestCase
{
    use DatabaseTransactions;
    use CreatesTenantFixtures;

    public function test_deleting_patient_removes_it_from_normal_queries_but_keeps_it_recoverable()
    {
        [$cabinet, $user] = $this->makeCabinetWithUser(9001, 'userSoft1');
        $this->actingAs($user);

        $patient = Patient::create(['Nom' => 'PatientSoft1', 'fkidcabinet' => $cabinet->idEntete]);

        Livewire::test(PatientManager::class)
            ->set('patientToDelete', $patient->ID)
            ->call('deletePatient');

        $this->assertNull(Patient::find($patient->ID));

        $trashed = Patient::withTrashed()->find($patient->ID);
        $this->assertNotNull($trashed);
        $this->assertNotNull($trashed->deleted_at);
    }

    public function test_deleting_patient_soft_deletes_linked_health_records()
    {
        [$cabinet, $user] = $this->makeCabinetWithUser(9002, 'userSoft2');
        $this->actingAs($user);

        $patient = Patient::create(['Nom' => 'PatientSoft2', 'fkidcabinet' => $cabinet->idEntete]);

        $dossier = DossierMedical::create([
            'fkidPatient' => $patient->ID,
            'fkidCabinet' => $cabinet->idEntete,
            'groupe_sanguin' => 'O+',
        ]);

        $consultation = ConsultationMedicale::create([
            'fkidPatient' => $patient->ID,
            'fkidCabinet' => $cabinet->idEntete,
            'date_consultation' => now(),
            'motif' => 'Contrôle',
        ]);

        $analyse = AnalysePatient::create([
            'fkidPatient' => $patient->ID,
            'fkidCabinet' => $cabinet->idEntete,
            'libelle' => 'Radio',
            'fichier_path' => 'analyses/test.pdf',
            'fichier_nom' => 'test.pdf',
        ]);

        $ordonnanceRef = Ordonnanceref::create([
            'refOrd' => 'REF-9002',
            'Annee' => now()->year,
            'numordre' => 1,
            'fkidpatient' => $patient->ID,
            'fkidprescripteur' => $user->Iduser,
            'fkidCabinet' => $cabinet->idEntete,
            'TypeOrdonnance' => 'Ordonnances',
        ]);

        $ordonnance = Ordonnance::create([
            'Libelle' => 'Paracetamol',
            'fkidrefOrd' => $ordonnanceRef->id,
            'NumordreOrd' => 1,
            'fkiduser' => $user->Iduser,
        ]);

        Livewire::test(PatientManager::class)
            ->set('patientToDelete', $patient->ID)
            ->call('deletePatient');

        $this->assertFalse(DossierMedical::where('fkidPatient', $patient->ID)->exists());
        $this->assertTrue(DossierMedical::withTrashed()->where('fkidPatient', $patient->ID)->exists());

        $this->assertFalse(ConsultationMedicale::where('fkidPatient', $patient->ID)->exists());
        $this->assertTrue(ConsultationMedicale::withTrashed()->where('fkidPatient', $patient->ID)->exists());

        $this->assertFalse(AnalysePatient::where('fkidPatient', $patient->ID)->exists());
        $this->assertTrue(AnalysePatient::withTrashed()->where('fkidPatient', $patient->ID)->exists());

        $this->assertFalse(Ordonnanceref::where('fkidpatient', $patient->ID)->exists());
        $this->assertTrue(Ordonnanceref::withTrashed()->where('fkidpatient', $patient->ID)->exists());

        $this->assertFalse(Ordonnance::where('fkidrefOrd', $ordonnanceRef->id)->exists());
        $this->assertTrue(
            Ordonnance::withoutTenant()->withTrashed()->where('fkidrefOrd', $ordonnanceRef->id)->exists()
        );
    }

    public function test_deleting_patient_still_hard_deletes_out_of_scope_data()
    {
        [$cabinet, $user] = $this->makeCabinetWithUser(9003, 'userSoft3');
        $this->actingAs($user);

        $patient = Patient::create(['Nom' => 'PatientSoft3', 'fkidcabinet' => $cabinet->idEntete]);

        $facture = Facture::create([
            'fkidCabinet' => $cabinet->idEntete,
            'IDPatient' => $patient->ID,
            'TotFacture' => 100,
        ]);

        DB::table('rendezvous')->insert([
            'fkidPatient' => $patient->ID,
            'fkidcabinet' => $cabinet->idEntete,
        ]);

        DB::table('mouvements_stock')->insert([
            'fkidStock' => 1,
            'fkidMedicament' => 1,
            'typeMouvement' => 'SORTIE',
            'quantite' => 1,
            'prixUnitaire' => 10,
            'montantTotal' => 10,
            'fkidPatient' => $patient->ID,
            'fkidUser' => $user->Iduser,
            'dateMouvement' => now(),
        ]);

        Livewire::test(PatientManager::class)
            ->set('patientToDelete', $patient->ID)
            ->call('deletePatient');

        $this->assertNull(Facture::withoutGlobalScopes()->find($facture->Idfacture));
        $this->assertFalse(DB::table('rendezvous')->where('fkidPatient', $patient->ID)->exists());
        $this->assertFalse(DB::table('mouvements_stock')->where('fkidPatient', $patient->ID)->exists());
    }

    public function test_soft_deleted_patient_is_isolated_per_cabinet()
    {
        [$cabinetA, $userA] = $this->makeCabinetWithUser(9004, 'userSoft4a');
        [$cabinetB, $userB] = $this->makeCabinetWithUser(9005, 'userSoft4b');

        $patientA = Patient::create(['Nom' => 'PatientSoft4A', 'fkidcabinet' => $cabinetA->idEntete]);

        $this->actingAs($userA);
        Livewire::test(PatientManager::class)
            ->set('patientToDelete', $patientA->ID)
            ->call('deletePatient');

        $this->actingAs($userB);
        $this->assertFalse(
            Patient::withTrashed()->where('ID', $patientA->ID)->exists(),
            'Un utilisateur du cabinet B ne doit pas voir le patient supprime du cabinet A, meme via withTrashed.'
        );

        $this->assertNotNull(Patient::withoutTenant()->withTrashed()->find($patientA->ID));
    }

    public function test_supprimer_analyse_soft_deletes_and_keeps_file()
    {
        Storage::fake('public');

        [$cabinet, $user] = $this->makeCabinetWithUser(9006, 'userSoft6');
        $this->actingAs($user);

        $patient = Patient::create(['Nom' => 'PatientSoft6', 'fkidcabinet' => $cabinet->idEntete]);

        Storage::disk('public')->put('analyses/keep.pdf', 'contenu');

        $analyse = AnalysePatient::create([
            'fkidPatient' => $patient->ID,
            'fkidCabinet' => $cabinet->idEntete,
            'libelle' => 'Radio',
            'fichier_path' => 'analyses/keep.pdf',
            'fichier_nom' => 'keep.pdf',
        ]);

        Livewire::test(DossierMedicalManager::class, ['patient' => $patient])
            ->call('supprimerAnalyse', $analyse->id);

        $this->assertFalse(AnalysePatient::where('id', $analyse->id)->exists());
        $this->assertNotNull(AnalysePatient::withTrashed()->find($analyse->id)->deleted_at);
        Storage::disk('public')->assertExists('analyses/keep.pdf');
    }

    public function test_supprimer_consultation_soft_deletes()
    {
        [$cabinet, $user] = $this->makeCabinetWithUser(9007, 'userSoft7');
        $this->actingAs($user);

        $patient = Patient::create(['Nom' => 'PatientSoft7', 'fkidcabinet' => $cabinet->idEntete]);

        $consultation = ConsultationMedicale::create([
            'fkidPatient' => $patient->ID,
            'fkidCabinet' => $cabinet->idEntete,
            'date_consultation' => now(),
            'motif' => 'Contrôle',
        ]);

        Livewire::test(DossierMedicalManager::class, ['patient' => $patient])
            ->call('supprimerConsultation', $consultation->id);

        $this->assertFalse(ConsultationMedicale::where('id', $consultation->id)->exists());
        $this->assertNotNull(ConsultationMedicale::withTrashed()->find($consultation->id)->deleted_at);
    }

    public function test_supprimer_ordonnance_soft_deletes_ref_and_lines()
    {
        [$cabinet, $user] = $this->makeCabinetWithUser(9008, 'userSoft8');
        $user->forceFill(['IdClasseUser' => 3])->save();
        $this->actingAs($user);

        $patient = Patient::create(['Nom' => 'PatientSoft8', 'fkidcabinet' => $cabinet->idEntete]);

        $ordonnanceRef = Ordonnanceref::create([
            'refOrd' => 'REF-9008',
            'Annee' => now()->year,
            'numordre' => 1,
            'fkidpatient' => $patient->ID,
            'fkidprescripteur' => $user->Iduser,
            'fkidCabinet' => $cabinet->idEntete,
            'TypeOrdonnance' => 'Ordonnances',
        ]);

        Ordonnance::create([
            'Libelle' => 'Ibuprofene',
            'fkidrefOrd' => $ordonnanceRef->id,
            'NumordreOrd' => 1,
            'fkiduser' => $user->Iduser,
        ]);

        Livewire::test(OrdonnanceManager::class, ['patient' => $patient])
            ->call('supprimerOrdonnance', $ordonnanceRef->id);

        $this->assertFalse(Ordonnanceref::where('id', $ordonnanceRef->id)->exists());
        $this->assertNotNull(Ordonnanceref::withTrashed()->find($ordonnanceRef->id)->deleted_at);

        $this->assertFalse(Ordonnance::where('fkidrefOrd', $ordonnanceRef->id)->exists());
        // Le scope tenant d'Ordonnance passe par un whereHas('ordonnanceRef', ...) qui exclut
        // nativement les Ordonnanceref soft-deleted : withoutTenant() est necessaire en plus
        // de withTrashed() pour retrouver la ligne, meme si Ordonnance elle-meme est bien
        // soft-deleted explicitement par supprimerOrdonnance().
        $this->assertTrue(
            Ordonnance::withoutTenant()->withTrashed()->where('fkidrefOrd', $ordonnanceRef->id)->exists()
        );
    }
}
