<?php

namespace Tests\Feature;

use App\Http\Livewire\SalleAttente;
use App\Http\Livewire\SalleSoins;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\Feature\Concerns\CreatesTenantFixtures;
use Tests\TestCase;

class SalleAttenteSoinsMobileModeTest extends TestCase
{
    use DatabaseTransactions;
    use CreatesTenantFixtures;

    public function test_salle_attente_renders_mobile_view_when_flag_set()
    {
        [$cabinet, $user] = $this->makeCabinetWithUser(19001, 'userMobileMode1');
        $this->actingAs($user);

        Livewire::test(SalleAttente::class, ['modeMobile' => true])
            ->assertViewIs('livewire.salle-attente-mobile');
    }

    public function test_salle_attente_renders_desktop_view_by_default()
    {
        [$cabinet, $user] = $this->makeCabinetWithUser(19002, 'userMobileMode2');
        $this->actingAs($user);

        Livewire::test(SalleAttente::class)
            ->assertViewIs('livewire.salle-attente');
    }

    public function test_salle_soins_renders_mobile_view_when_flag_set()
    {
        [$cabinet, $user] = $this->makeCabinetWithUser(19003, 'userMobileMode3');
        $this->actingAs($user);

        Livewire::test(SalleSoins::class, ['modeMobile' => true])
            ->assertViewIs('livewire.salle-soins-mobile');
    }

    public function test_salle_soins_renders_desktop_view_by_default()
    {
        [$cabinet, $user] = $this->makeCabinetWithUser(19004, 'userMobileMode4');
        $this->actingAs($user);

        Livewire::test(SalleSoins::class)
            ->assertViewIs('livewire.salle-soins');
    }
}
