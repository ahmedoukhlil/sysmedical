<?php

namespace App\Services;

use App\Models\AnalysePatient;
use App\Models\CabinetSubscription;

class StorageQuotaService
{
    public function usedBytes(int $idEntete): int
    {
        return (int) AnalysePatient::withoutTenant()
            ->withTrashed()
            ->where('fkidCabinet', $idEntete)
            ->sum('fichier_taille');
    }

    public function quotaBytes(int $idEntete): ?int
    {
        $subscription = CabinetSubscription::where('idEntete', $idEntete)->with('plan')->first();

        if (!$subscription || !$subscription->plan || $subscription->plan->max_storage_mb === null) {
            return null;
        }

        return $subscription->plan->max_storage_mb * 1024 * 1024;
    }

    public function hasRoomFor(int $idEntete, int $additionalBytes): bool
    {
        $quota = $this->quotaBytes($idEntete);

        if ($quota === null) {
            return true;
        }

        return ($this->usedBytes($idEntete) + $additionalBytes) <= $quota;
    }

    public function remainingBytes(int $idEntete): ?int
    {
        $quota = $this->quotaBytes($idEntete);

        if ($quota === null) {
            return null;
        }

        return max(0, $quota - $this->usedBytes($idEntete));
    }
}
