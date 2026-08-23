<?php

namespace Tests\Feature;

use App\Models\Facture;
use App\Models\Infocabinet;
use App\Models\Patient;
use App\Models\TUser;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use DatabaseTransactions;

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

    public function test_patient_list_is_scoped_to_current_cabinet()
    {
        [$cabinetA, $userA] = $this->makeCabinetWithUser(1001, 'userA');
        [$cabinetB, $userB] = $this->makeCabinetWithUser(1002, 'userB');

        $patientA = Patient::create(['Nom' => 'PatientA', 'fkidcabinet' => $cabinetA->idEntete]);
        $patientB = Patient::create(['Nom' => 'PatientB', 'fkidcabinet' => $cabinetB->idEntete]);

        $this->actingAs($userA);

        $visible = Patient::all();
        $this->assertCount(1, $visible);
        $this->assertEquals($patientA->ID, $visible->first()->ID);

        $this->assertNull(Patient::find($patientB->ID));

        $this->assertNotNull(
            Patient::withoutGlobalScopes()->find($patientB->ID),
            'Le patient B doit exister en base, seul le scope doit le masquer.'
        );
    }

    public function test_facture_mass_update_is_scoped_to_current_cabinet()
    {
        [$cabinetA, $userA] = $this->makeCabinetWithUser(2001, 'userA2');
        [$cabinetB, $userB] = $this->makeCabinetWithUser(2002, 'userB2');

        $factureB = Facture::create([
            'fkidCabinet' => $cabinetB->idEntete,
            'TotFacture' => 100,
        ]);

        $this->actingAs($userA);

        $affected = Facture::where('Idfacture', $factureB->Idfacture)
            ->update(['TotFacture' => 999]);

        $this->assertEquals(0, $affected);

        $this->assertEquals(
            100,
            Facture::withoutGlobalScopes()->find($factureB->Idfacture)->TotFacture
        );
    }

    public function test_scope_does_not_filter_without_authenticated_user()
    {
        [$cabinetA, $userA] = $this->makeCabinetWithUser(3001, 'userA3');
        [$cabinetB, $userB] = $this->makeCabinetWithUser(3002, 'userB3');

        Patient::create(['Nom' => 'PatientA3', 'fkidcabinet' => $cabinetA->idEntete]);
        Patient::create(['Nom' => 'PatientB3', 'fkidcabinet' => $cabinetB->idEntete]);

        Auth::logout();

        $this->assertGreaterThanOrEqual(2, Patient::count());
    }
}
