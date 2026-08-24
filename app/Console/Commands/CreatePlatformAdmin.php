<?php

namespace App\Console\Commands;

use App\Models\PlatformAdmin;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class CreatePlatformAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:create-super-admin {email} {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Créer un compte super-admin plateforme';

    public function handle()
    {
        $email = $this->argument('email');
        $name = $this->argument('name');

        $validator = Validator::make(
            ['email' => $email],
            ['email' => 'required|email|unique:platform_admins,email']
        );

        if ($validator->fails()) {
            $this->error($validator->errors()->first());
            return self::FAILURE;
        }

        $password = $this->secret('Mot de passe');
        $passwordConfirmation = $this->secret('Confirmer le mot de passe');

        if ($password !== $passwordConfirmation) {
            $this->error('Les mots de passe ne correspondent pas.');
            return self::FAILURE;
        }

        if (strlen($password) < 8) {
            $this->error('Le mot de passe doit contenir au moins 8 caractères.');
            return self::FAILURE;
        }

        PlatformAdmin::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ]);

        $this->info("Super-admin créé : {$email}");
        return self::SUCCESS;
    }
}
