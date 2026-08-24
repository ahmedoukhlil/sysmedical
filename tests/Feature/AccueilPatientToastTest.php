<?php

namespace Tests\Feature;

use App\Http\Livewire\AccueilPatient;
use App\Http\Livewire\ParametresCabinet;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\Feature\Concerns\CreatesTenantFixtures;
use Tests\TestCase;

class AccueilPatientToastTest extends TestCase
{
    use DatabaseTransactions;
    use CreatesTenantFixtures;

    public function test_ouvrir_urgence_modal_without_patient_emits_error_toast()
    {
        [$cabinet, $user] = $this->makeCabinetWithUser(11001, 'userToast1');
        $this->actingAs($user);

        Livewire::test(AccueilPatient::class)
            ->call('ouvrirUrgenceModal')
            ->assertEmitted('toast', function ($eventName, $params) {
                return ($params[0]['type'] ?? null) === 'error';
            });
    }

    public function test_ordonnance_created_emits_success_toast()
    {
        [$cabinet, $user] = $this->makeCabinetWithUser(11002, 'userToast2');
        $this->actingAs($user);

        Livewire::test(AccueilPatient::class)
            ->call('handleOrdonnanceCreated', 1)
            ->assertEmitted('toast', function ($eventName, $params) {
                return ($params[0]['type'] ?? null) === 'success';
            });
    }

    public function test_parametres_cabinet_save_emits_success_toast()
    {
        [$cabinet, $user] = $this->makeCabinetWithUser(11003, 'userToast3');
        $this->actingAs($user);

        Livewire::test(ParametresCabinet::class)
            ->call('save')
            ->assertEmitted('toast', function ($eventName, $params) {
                return ($params[0]['type'] ?? null) === 'success';
            });
    }
}
