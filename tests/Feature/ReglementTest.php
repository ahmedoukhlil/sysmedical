<?php

namespace Tests\Feature;

use App\Models\Facture;
use App\Models\Reglement;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Feature\Concerns\CreatesTenantFixtures;
use Tests\TestCase;

class ReglementTest extends TestCase
{
    use DatabaseTransactions;
    use CreatesTenantFixtures;

    public function test_reglement_is_linked_to_its_facture()
    {
        [$cabinet, $user] = $this->makeCabinetWithUser(6001, 'userRegl1');
        $this->actingAs($user);

        $facture = Facture::create([
            'fkidCabinet' => $cabinet->idEntete,
            'TotFacture' => 500,
            'TotReglPatient' => 0,
        ]);

        $reglement = Reglement::create([
            'fkidFactBord' => $facture->Idfacture,
            'dtreglement' => now(),
            'typeReglement' => 'Especes',
            'MontantRec' => 500,
            'fkiduser' => $user->Iduser,
        ]);

        $this->assertEquals($facture->Idfacture, $reglement->facture->Idfacture);
    }

    public function test_reglement_is_scoped_to_current_cabinet_via_facture()
    {
        [$cabinetA, $userA] = $this->makeCabinetWithUser(6002, 'userRegl2');
        [$cabinetB, $userB] = $this->makeCabinetWithUser(6003, 'userRegl3');

        $factureA = Facture::create(['fkidCabinet' => $cabinetA->idEntete, 'TotFacture' => 100]);
        $factureB = Facture::create(['fkidCabinet' => $cabinetB->idEntete, 'TotFacture' => 200]);

        Reglement::create([
            'fkidFactBord' => $factureA->Idfacture,
            'dtreglement' => now(),
            'MontantRec' => 100,
            'fkiduser' => $userA->Iduser,
        ]);

        Reglement::create([
            'fkidFactBord' => $factureB->Idfacture,
            'dtreglement' => now(),
            'MontantRec' => 200,
            'fkiduser' => $userB->Iduser,
        ]);

        $this->actingAs($userA);

        $visible = Reglement::all();

        $this->assertCount(1, $visible, 'Un utilisateur du cabinet A ne doit voir que les reglements de ses factures.');
        $this->assertEquals($factureA->Idfacture, $visible->first()->fkidFactBord);
    }

    public function test_facture_balance_updates_after_partial_payment()
    {
        [$cabinet, $user] = $this->makeCabinetWithUser(6004, 'userRegl4');
        $this->actingAs($user);

        $facture = Facture::create([
            'fkidCabinet' => $cabinet->idEntete,
            'TotFacture' => 300,
            'TotReglPatient' => 0,
        ]);

        Reglement::create([
            'fkidFactBord' => $facture->Idfacture,
            'dtreglement' => now(),
            'MontantRec' => 100,
            'fkiduser' => $user->Iduser,
        ]);

        $facture->TotReglPatient = $facture->reglements()->sum('MontantRec');
        $facture->save();
        $facture->refresh();

        $this->assertEquals(100, $facture->TotReglPatient);
        $this->assertEquals(200, $facture->TotFacture - $facture->TotReglPatient, 'Le solde restant doit refleter le paiement partiel.');
    }
}
