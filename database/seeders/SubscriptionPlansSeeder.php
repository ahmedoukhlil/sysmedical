<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlansSeeder extends Seeder
{
    public function run()
    {
        $plans = [
            [
                'code' => 'essentiel',
                'nom' => 'Essentiel',
                'prix_mensuel' => 1500,
                'devise' => 'MRU',
                'description' => 'Pour démarrer : patients, rendez-vous, consultation, facturation simple.',
                'fonctionnalites' => [
                    '1 utilisateur',
                    'Gestion des patients',
                    'Rendez-vous',
                    'Facturation simple',
                ],
                'max_users' => 1,
                'max_storage_mb' => 500,
                'actif' => true,
                'ordre' => 1,
            ],
            [
                'code' => 'standard',
                'nom' => 'Standard',
                'prix_mensuel' => 3000,
                'devise' => 'MRU',
                'description' => 'Le workflow complet : ordonnances, dossier médical, portail patient.',
                'fonctionnalites' => [
                    "Jusqu'à 5 utilisateurs",
                    'Ordonnances',
                    'Dossier médical',
                    'Portail patient QR code',
                ],
                'max_users' => 5,
                'max_storage_mb' => 2048,
                'actif' => true,
                'ordre' => 2,
            ],
            [
                'code' => 'pro',
                'nom' => 'Pro',
                'prix_mensuel' => 5000,
                'devise' => 'MRU',
                'description' => 'Pour les cabinets et groupes ambitieux : pharmacie, assurance, multi-sites.',
                'fonctionnalites' => [
                    'Utilisateurs illimités',
                    'Gestion de stock pharmacie',
                    'Tiers-payant assurance',
                    'Statistiques multi-médecins',
                ],
                'max_users' => null,
                'max_storage_mb' => null,
                'actif' => true,
                'ordre' => 3,
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::updateOrCreate(['code' => $plan['code']], $plan);
        }
    }
}
