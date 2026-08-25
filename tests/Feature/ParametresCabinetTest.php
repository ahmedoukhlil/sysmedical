<?php

namespace Tests\Feature;

use App\Http\Livewire\ParametresCabinet;
use App\Models\Infocabinet;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\Feature\Concerns\CreatesTenantFixtures;
use Tests\TestCase;

class ParametresCabinetTest extends TestCase
{
    use DatabaseTransactions;
    use CreatesTenantFixtures;

    public function test_save_persists_both_fr_and_ar_values()
    {
        [$cabinet, $user] = $this->makeCabinetWithUser(13001, 'userCabinet1');
        $this->actingAs($user);

        Livewire::test(ParametresCabinet::class)
            ->set('NomCabFr', 'Cabinet Dentaire Test')
            ->set('NomCabAr', 'عيادة الأسنان التجريبية')
            ->set('DrFr', 'Dr. Test Fr')
            ->set('DrAr', 'د. تيست')
            ->call('save');

        $this->assertDatabaseHas('infocabinet', [
            'idEntete' => 13001,
            'NomCabFr' => 'Cabinet Dentaire Test',
            'NomCabAr' => 'عيادة الأسنان التجريبية',
            'DrFr' => 'Dr. Test Fr',
            'DrAr' => 'د. تيست',
        ]);
    }

    public function test_infocabinet_trans_returns_value_for_requested_locale()
    {
        [$cabinet] = $this->makeCabinetWithUser(13002, 'userCabinet2');
        $cabinet->forceFill([
            'NomCabFr' => 'Nom Français',
            'NomCabAr' => 'الاسم العربي',
        ])->save();

        $this->assertEquals('Nom Français', $cabinet->trans('nom_cab', 'fr'));
        $this->assertEquals('الاسم العربي', $cabinet->trans('nom_cab', 'ar'));
    }
}
