<?php

namespace Tests\Feature\Concerns;

use App\Models\Infocabinet;
use App\Models\TUser;

trait CreatesTenantFixtures
{
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
}
