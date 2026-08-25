<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Feature\Concerns\CreatesTenantFixtures;
use Tests\TestCase;

class DocumentPrintTenantIsolationTest extends TestCase
{
    use DatabaseTransactions;
    use CreatesTenantFixtures;

    public function test_recu_header_falls_back_to_authenticated_users_cabinet_not_first_record()
    {
        // Cabinet B est créé en premier (idEntete le plus bas) pour que l'ancien
        // comportement bugué Infocabinet::first() renverrait Cabinet B et non Cabinet A.
        [$cabinetB] = $this->makeCabinetWithUser(14001, 'userTenantB');
        $cabinetB->forceFill(['NomCabFr' => 'Cabinet B'])->save();

        [$cabinetA, $userA] = $this->makeCabinetWithUser(14002, 'userTenantA');
        $cabinetA->forceFill(['NomCabFr' => 'Cabinet A'])->save();

        $this->actingAs($userA);

        $html = view('partials.recu-header')->render();

        $this->assertStringContainsString('Cabinet A', $html);
        $this->assertStringNotContainsString('Cabinet B', $html);
    }
}
