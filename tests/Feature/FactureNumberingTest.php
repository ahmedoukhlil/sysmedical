<?php

namespace Tests\Feature;

use App\Models\Facture;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Feature\Concerns\CreatesTenantFixtures;
use Tests\TestCase;

class FactureNumberingTest extends TestCase
{
    use DatabaseTransactions;
    use CreatesTenantFixtures;

    public function test_first_facture_of_the_year_starts_at_one()
    {
        [$cabinet, $user] = $this->makeCabinetWithUser(4001, 'userFact1');
        $this->actingAs($user);

        $result = Facture::generateUniqueFactureNumber($cabinet->idEntete, 2026);

        $this->assertEquals(1, $result['nordre']);
        $this->assertEquals('1-2026', $result['Nfacture']);
    }

    public function test_facture_number_increments_from_last_one_of_the_year()
    {
        [$cabinet, $user] = $this->makeCabinetWithUser(4002, 'userFact2');
        $this->actingAs($user);

        Facture::create([
            'fkidCabinet' => $cabinet->idEntete,
            'anneeFacture' => 2026,
            'nordre' => 5,
            'Nfacture' => '5-2026',
        ]);

        $result = Facture::generateUniqueFactureNumber($cabinet->idEntete, 2026);

        $this->assertEquals(6, $result['nordre']);
        $this->assertEquals('6-2026', $result['Nfacture']);
    }

    public function test_facture_numbering_is_independent_per_cabinet()
    {
        [$cabinetA, $userA] = $this->makeCabinetWithUser(4003, 'userFact3');
        [$cabinetB, $userB] = $this->makeCabinetWithUser(4004, 'userFact4');

        Facture::create([
            'fkidCabinet' => $cabinetA->idEntete,
            'anneeFacture' => 2026,
            'nordre' => 10,
            'Nfacture' => '10-2026',
        ]);

        $resultB = Facture::generateUniqueFactureNumber($cabinetB->idEntete, 2026);

        $this->assertEquals(1, $resultB['nordre'], 'La numerotation du cabinet B ne doit pas dependre du cabinet A.');
    }

    public function test_facture_numbering_resets_per_year()
    {
        [$cabinet, $user] = $this->makeCabinetWithUser(4005, 'userFact5');
        $this->actingAs($user);

        Facture::create([
            'fkidCabinet' => $cabinet->idEntete,
            'anneeFacture' => 2025,
            'nordre' => 42,
            'Nfacture' => '42-2025',
        ]);

        $result = Facture::generateUniqueFactureNumber($cabinet->idEntete, 2026);

        $this->assertEquals(1, $result['nordre'], 'Une nouvelle annee doit repartir a 1.');
    }
}
