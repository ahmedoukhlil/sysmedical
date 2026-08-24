<?php

namespace Tests\Feature;

use App\Http\Livewire\DossierMedicalManager;
use App\Models\AnalysePatient;
use App\Models\CabinetSubscription;
use App\Models\ConsultationMedicale;
use App\Models\DossierMedical;
use App\Models\Patient;
use App\Models\SubscriptionPlan;
use App\Services\CabinetExportService;
use App\Services\StorageQuotaService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Feature\Concerns\CreatesTenantFixtures;
use Tests\TestCase;

class StorageQuotaAndExportTest extends TestCase
{
    use DatabaseTransactions;
    use CreatesTenantFixtures;

    private function makePlan(string $code, ?int $maxStorageMb): SubscriptionPlan
    {
        return SubscriptionPlan::updateOrCreate(
            ['code' => $code],
            [
                'nom' => ucfirst($code),
                'prix_mensuel' => 1500,
                'devise' => 'MRU',
                'max_storage_mb' => $maxStorageMb,
                'actif' => true,
                'ordre' => 1,
            ]
        );
    }

    public function test_upload_is_allowed_within_quota()
    {
        Storage::fake('public');

        [$cabinet, $user] = $this->makeCabinetWithUser(10001, 'userQuota1');
        $plan = $this->makePlan('essentiel-q1', 1);
        CabinetSubscription::create([
            'idEntete' => $cabinet->idEntete,
            'subscription_plan_id' => $plan->id,
            'statut' => CabinetSubscription::STATUT_ACTIF,
            'current_period_ends_at' => now()->addMonth(),
        ]);

        $this->actingAs($user);
        $patient = Patient::create(['Nom' => 'PatientQuota1', 'fkidcabinet' => $cabinet->idEntete]);

        $fichier = UploadedFile::fake()->create('test.pdf', 100);

        Livewire::test(DossierMedicalManager::class, ['patient' => $patient])
            ->set('analysesFichiers', [$fichier])
            ->set('analyseLibelle', 'Radio')
            ->call('uploadAnalyses');

        $this->assertEquals(1, AnalysePatient::where('fkidPatient', $patient->ID)->count());
    }

    public function test_upload_is_blocked_when_quota_exceeded()
    {
        Storage::fake('public');

        [$cabinet, $user] = $this->makeCabinetWithUser(10002, 'userQuota2');
        $plan = $this->makePlan('essentiel-q2', 1); // 1 Mo
        CabinetSubscription::create([
            'idEntete' => $cabinet->idEntete,
            'subscription_plan_id' => $plan->id,
            'statut' => CabinetSubscription::STATUT_ACTIF,
            'current_period_ends_at' => now()->addMonth(),
        ]);

        $this->actingAs($user);
        $patient = Patient::create(['Nom' => 'PatientQuota2', 'fkidcabinet' => $cabinet->idEntete]);

        // 1500 Ko, sous la limite de validation individuelle (10 Mo) mais depasse le quota cabinet de 1 Mo
        $fichier = UploadedFile::fake()->create('gros.pdf', 1500);

        Livewire::test(DossierMedicalManager::class, ['patient' => $patient])
            ->set('analysesFichiers', [$fichier])
            ->set('analyseLibelle', 'Radio')
            ->call('uploadAnalyses');

        $this->assertEquals(0, AnalysePatient::where('fkidPatient', $patient->ID)->count());

        $quotaService = app(StorageQuotaService::class);
        $this->assertFalse($quotaService->hasRoomFor($cabinet->idEntete, 1500 * 1024));
    }

    public function test_upload_is_always_allowed_on_unlimited_plan()
    {
        Storage::fake('public');

        [$cabinet, $user] = $this->makeCabinetWithUser(10003, 'userQuota3');
        $plan = $this->makePlan('pro-q3', null); // illimite
        CabinetSubscription::create([
            'idEntete' => $cabinet->idEntete,
            'subscription_plan_id' => $plan->id,
            'statut' => CabinetSubscription::STATUT_ACTIF,
            'current_period_ends_at' => now()->addMonth(),
        ]);

        $this->actingAs($user);
        $patient = Patient::create(['Nom' => 'PatientQuota3', 'fkidcabinet' => $cabinet->idEntete]);

        $fichier = UploadedFile::fake()->create('gros.pdf', 5000);

        Livewire::test(DossierMedicalManager::class, ['patient' => $patient])
            ->set('analysesFichiers', [$fichier])
            ->set('analyseLibelle', 'Radio')
            ->call('uploadAnalyses');

        $this->assertEquals(1, AnalysePatient::where('fkidPatient', $patient->ID)->count());
    }

    public function test_soft_deleted_analyses_still_count_towards_quota()
    {
        [$cabinet, $user] = $this->makeCabinetWithUser(10004, 'userQuota4');
        $this->actingAs($user);

        $patient = Patient::create(['Nom' => 'PatientQuota4', 'fkidcabinet' => $cabinet->idEntete]);

        $analyse = AnalysePatient::create([
            'fkidPatient' => $patient->ID,
            'fkidCabinet' => $cabinet->idEntete,
            'libelle' => 'Radio',
            'fichier_path' => 'analyses/test.pdf',
            'fichier_nom' => 'test.pdf',
            'fichier_taille' => 1024 * 1024, // 1 Mo
        ]);

        $quotaService = app(StorageQuotaService::class);
        $before = $quotaService->usedBytes($cabinet->idEntete);

        $analyse->delete(); // soft delete

        $after = $quotaService->usedBytes($cabinet->idEntete);

        $this->assertEquals($before, $after);
        $this->assertEquals(1024 * 1024, $after);
    }

    public function test_export_generates_non_empty_archive()
    {
        [$cabinet, $user] = $this->makeCabinetWithUser(10005, 'userQuota5');
        $this->actingAs($user);

        $patient = Patient::create(['Nom' => 'PatientQuota5', 'fkidcabinet' => $cabinet->idEntete]);

        DossierMedical::create([
            'fkidPatient' => $patient->ID,
            'fkidCabinet' => $cabinet->idEntete,
            'groupe_sanguin' => 'O+',
        ]);

        ConsultationMedicale::create([
            'fkidPatient' => $patient->ID,
            'fkidCabinet' => $cabinet->idEntete,
            'date_consultation' => now(),
            'motif' => 'Controle',
        ]);

        $service = app(CabinetExportService::class);
        $zipPath = $service->export($cabinet->idEntete);

        $this->assertFileExists($zipPath);

        $zip = new \ZipArchive();
        $zip->open($zipPath);
        $this->assertGreaterThan(0, $zip->numFiles);
        $this->assertNotFalse($zip->locateName('donnees/patients.json'));
        $zip->close();

        unlink($zipPath);
    }
}
