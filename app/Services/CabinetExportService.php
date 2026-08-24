<?php

namespace App\Services;

use App\Models\AnalysePatient;
use App\Models\ConsultationMedicale;
use App\Models\DossierMedical;
use App\Models\Facture;
use App\Models\Infocabinet;
use App\Models\Ordonnanceref;
use App\Models\Patient;
use ZipArchive;

class CabinetExportService
{
    public function export(int $idEntete): string
    {
        Infocabinet::findOrFail($idEntete);

        $tempDir = storage_path('app/temp/exports');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $filename = sprintf('cabinet_%d_export_%s.zip', $idEntete, now()->format('Y-m-d_His'));
        $zipPath = $tempDir . DIRECTORY_SEPARATOR . $filename;

        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $this->addJsonEntities($zip, $idEntete);
        $this->addPhysicalFiles($zip, $idEntete);

        $zip->close();

        return $zipPath;
    }

    private function addJsonEntities(ZipArchive $zip, int $idEntete): void
    {
        $zip->addFromString('donnees/patients.json',
            Patient::withoutTenant()->withTrashed()->where('fkidcabinet', $idEntete)->get()->toJson(JSON_PRETTY_PRINT));

        $zip->addFromString('donnees/dossiers_medicaux.json',
            DossierMedical::withoutTenant()->withTrashed()->where('fkidCabinet', $idEntete)->get()->toJson(JSON_PRETTY_PRINT));

        $zip->addFromString('donnees/consultations.json',
            ConsultationMedicale::withoutTenant()->withTrashed()->where('fkidCabinet', $idEntete)->get()->toJson(JSON_PRETTY_PRINT));

        $zip->addFromString('donnees/analyses.json',
            AnalysePatient::withoutTenant()->withTrashed()->where('fkidCabinet', $idEntete)
                ->get(['id', 'fkidPatient', 'libelle', 'type', 'fichier_nom', 'fichier_mime', 'fichier_taille', 'date_analyse', 'notes'])
                ->toJson(JSON_PRETTY_PRINT));

        $ordonnanceRefs = Ordonnanceref::withoutTenant()->withTrashed()->where('fkidCabinet', $idEntete)->get();
        $zip->addFromString('donnees/ordonnances.json', $ordonnanceRefs->toJson(JSON_PRETTY_PRINT));

        $zip->addFromString('donnees/factures.json',
            Facture::withoutTenant()->where('fkidCabinet', $idEntete)->get()->toJson(JSON_PRETTY_PRINT));
    }

    private function addPhysicalFiles(ZipArchive $zip, int $idEntete): void
    {
        $baseDir = storage_path('app/public/analyses/' . $idEntete);
        if (!is_dir($baseDir)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($baseDir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            $localPath = 'fichiers/analyses/' . $idEntete . '/' . substr($file->getPathname(), strlen($baseDir) + 1);
            $zip->addFile($file->getPathname(), str_replace('\\', '/', $localPath));
        }
    }
}
