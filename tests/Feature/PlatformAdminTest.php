<?php

namespace Tests\Feature;

use App\Models\PlatformAdmin;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Feature\Concerns\CreatesTenantFixtures;
use Tests\TestCase;

class PlatformAdminTest extends TestCase
{
    use DatabaseTransactions;
    use CreatesTenantFixtures;

    public function test_super_admin_sees_all_cabinets_across_tenants()
    {
        [$cabinetA] = $this->makeCabinetWithUser(7001, 'userAdmin1');
        [$cabinetB] = $this->makeCabinetWithUser(7002, 'userAdmin2');

        $admin = PlatformAdmin::create([
            'name' => 'Super Admin',
            'email' => 'admin7001@platform.test',
            'password' => 'secret123',
        ]);

        $response = $this->actingAs($admin, 'admin')->get(route('admin.cabinets.index'));

        $response->assertOk();
        $response->assertSee($cabinetA->NomCabFr);
        $response->assertSee($cabinetB->NomCabFr);
    }

    public function test_normal_user_login_blocked_when_cabinet_suspended()
    {
        [$cabinet, $user] = $this->makeCabinetWithUser(7003, 'suspendedUser1');
        $cabinet->forceFill(['statut' => 'suspendu'])->save();

        $response = $this->post('/login', [
            'login' => 'suspendedUser1',
            'password' => 'secret',
        ]);

        $response->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_normal_user_login_succeeds_when_cabinet_active()
    {
        [$cabinet, $user] = $this->makeCabinetWithUser(7004, 'activeUser1');

        $response = $this->post('/login', [
            'login' => 'activeUser1',
            'password' => 'secret',
        ]);

        $response->assertSessionDoesntHaveErrors();
        $this->assertAuthenticatedAs($user);
    }
}
