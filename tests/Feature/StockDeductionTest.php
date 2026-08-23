<?php

namespace Tests\Feature;

use App\Models\Detailfacturepatient;
use App\Models\Facture;
use App\Models\Medicament;
use App\Models\MouvementStock;
use App\Models\StockMedicament;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\CreatesTenantFixtures;
use Tests\TestCase;

/**
 * Reproduit le chemin critique de facturation d'une ordonnance "urgence"
 * (App\Http\Livewire\OrdonnanceManager::save, lignes ~423-503) : deduction
 * du stock, creation du mouvement, mise a jour du total facture.
 */
class StockDeductionTest extends TestCase
{
    use DatabaseTransactions;
    use CreatesTenantFixtures;

    private function deductStockAndBillMedicament(
        StockMedicament $stock,
        Medicament $medicament,
        Facture $facture,
        float $quantite,
        int $userId
    ): Detailfacturepatient {
        return DB::transaction(function () use ($stock, $medicament, $facture, $quantite, $userId) {
            $prix = $medicament->PrixRef ?? 0;

            $stock->quantiteStock -= $quantite;
            $stock->dateDerniereSortie = now();
            $stock->save();

            $detail = Detailfacturepatient::create([
                'fkidfacture' => $facture->Idfacture,
                'DtAjout' => now(),
                'Actes' => $medicament->LibelleMedic,
                'PrixRef' => $prix,
                'PrixFacture' => $prix,
                'Quantite' => $quantite,
                'fkidmedicament' => $medicament->IDMedic,
                'IsAct' => 2,
                'fkidcabinet' => $facture->fkidCabinet,
            ]);

            $montantLigne = $prix * $quantite;
            $facture->TotFacture = ($facture->TotFacture ?? 0) + $montantLigne;
            $facture->save();

            MouvementStock::create([
                'fkidStock' => $stock->idStock,
                'fkidMedicament' => $medicament->IDMedic,
                'typeMouvement' => 'sortie',
                'quantite' => $quantite,
                'prixUnitaire' => $prix,
                'montantTotal' => $montantLigne,
                'fkidFacture' => $facture->Idfacture,
                'fkidDetailFacture' => $detail->idDetfacture,
                'fkidUser' => $userId,
                'dateMouvement' => now(),
            ]);

            return $detail;
        });
    }

    public function test_deducting_stock_creates_movement_and_updates_facture_total()
    {
        [$cabinet, $user] = $this->makeCabinetWithUser(5001, 'userStock1');
        $this->actingAs($user);

        $medicament = Medicament::create([
            'LibelleMedic' => 'Paracetamol',
            'fkidtype' => 1,
            'PrixRef' => 50,
        ]);

        $stock = StockMedicament::create([
            'fkidMedicament' => $medicament->IDMedic,
            'fkidCabinet' => $cabinet->idEntete,
            'quantiteStock' => 10,
            'quantiteMin' => 2,
            'prixAchat' => 30,
            'prixVente' => 50,
            'Masquer' => 0,
        ]);

        $facture = Facture::create([
            'fkidCabinet' => $cabinet->idEntete,
            'TotFacture' => 0,
            'estfacturer' => 0,
        ]);

        $detail = $this->deductStockAndBillMedicament($stock, $medicament, $facture, 3, $user->Iduser);

        $stock->refresh();
        $facture->refresh();

        $this->assertEquals(7, $stock->quantiteStock, 'Le stock doit etre decremente de la quantite prescrite.');
        $this->assertEquals(150, $facture->TotFacture, 'Le total facture doit inclure prix x quantite.');

        $mouvement = MouvementStock::where('fkidDetailFacture', $detail->idDetfacture)->first();
        $this->assertNotNull($mouvement, 'Un mouvement de stock doit etre trace pour audit.');
        // La colonne typeMouvement est un enum('ENTREE','SORTIE','AJUSTEMENT') en base
        // (migration create_missing_tables.php:105) ; MySQL normalise la casse a l'insertion
        // meme si OrdonnanceManager.php:487 insere la valeur en minuscule ('sortie').
        $this->assertEqualsIgnoringCase('sortie', $mouvement->typeMouvement);
        $this->assertEquals(3, $mouvement->quantite);
        $this->assertEquals(150, $mouvement->montantTotal);
    }

    public function test_stock_movement_is_scoped_to_its_cabinet_via_stock_relation()
    {
        [$cabinetA, $userA] = $this->makeCabinetWithUser(5002, 'userStock2');
        [$cabinetB, $userB] = $this->makeCabinetWithUser(5003, 'userStock3');

        $medicamentA = Medicament::create(['LibelleMedic' => 'MedA', 'fkidtype' => 1, 'PrixRef' => 20]);
        $medicamentB = Medicament::create(['LibelleMedic' => 'MedB', 'fkidtype' => 1, 'PrixRef' => 20]);

        $stockA = StockMedicament::create([
            'fkidMedicament' => $medicamentA->IDMedic,
            'fkidCabinet' => $cabinetA->idEntete,
            'quantiteStock' => 5,
            'quantiteMin' => 1,
            'prixAchat' => 10,
            'prixVente' => 20,
            'Masquer' => 0,
        ]);

        $stockB = StockMedicament::create([
            'fkidMedicament' => $medicamentB->IDMedic,
            'fkidCabinet' => $cabinetB->idEntete,
            'quantiteStock' => 5,
            'quantiteMin' => 1,
            'prixAchat' => 10,
            'prixVente' => 20,
            'Masquer' => 0,
        ]);

        MouvementStock::create([
            'fkidStock' => $stockA->idStock,
            'fkidMedicament' => $medicamentA->IDMedic,
            'typeMouvement' => 'sortie',
            'quantite' => 1,
            'prixUnitaire' => 20,
            'montantTotal' => 20,
            'fkidUser' => $userA->Iduser,
            'dateMouvement' => now(),
        ]);

        MouvementStock::create([
            'fkidStock' => $stockB->idStock,
            'fkidMedicament' => $medicamentB->IDMedic,
            'typeMouvement' => 'sortie',
            'quantite' => 1,
            'prixUnitaire' => 20,
            'montantTotal' => 20,
            'fkidUser' => $userB->Iduser,
            'dateMouvement' => now(),
        ]);

        $this->actingAs($userA);

        $visible = MouvementStock::all();

        $this->assertCount(1, $visible, 'Un utilisateur du cabinet A ne doit voir que les mouvements de son stock.');
        $this->assertEquals($stockA->idStock, $visible->first()->fkidStock);
    }
}
