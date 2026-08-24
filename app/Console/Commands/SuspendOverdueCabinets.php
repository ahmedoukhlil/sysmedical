<?php

namespace App\Console\Commands;

use App\Services\SubscriptionService;
use Illuminate\Console\Command;

class SuspendOverdueCabinets extends Command
{
    protected $signature = 'cabinets:suspend-overdue';

    protected $description = "Suspend les cabinets en essai expiré ou en période payante expirée sans paiement enregistré";

    public function handle(SubscriptionService $service)
    {
        $result = $service->processAllOverdue();

        $this->info("Abonnements passés en impayé : {$result['passes_impaye']}");
        $this->info("Cabinets suspendus : {$result['suspendus']}");

        return self::SUCCESS;
    }
}
