<?php

namespace App\Support;

class RdvStatus
{
    /**
     * Normalise la valeur brute de la colonne rdvConfirmer (variantes de casse
     * incohérentes en base) vers une clé canonique utilisable avec __('rdv.statuts.*').
     */
    public static function normalize(?string $statut): string
    {
        switch ($statut) {
            case 'En Attente':
            case 'En attente':
                return 'en_attente';
            case 'confirmé':
            case 'Confirmé':
                return 'confirme';
            case 'En cours':
                return 'en_cours';
            case 'terminé':
            case 'Terminé':
                return 'termine';
            case 'annulé':
            case 'Annulé':
                return 'annule';
            case 'Consultation':
                return 'consultation';
            default:
                return 'en_attente';
        }
    }

    public static function badgeClasses(string $cle): string
    {
        return match ($cle) {
            'en_attente' => 'bg-yellow-100 text-yellow-800',
            'confirme' => 'bg-blue-100 text-blue-800',
            'en_cours' => 'bg-green-100 text-green-800',
            'termine' => 'bg-gray-100 text-gray-800',
            'annule' => 'bg-red-100 text-red-800',
            'consultation' => 'bg-purple-100 text-purple-800',
            default => 'bg-yellow-100 text-yellow-800',
        };
    }

    public static function icon(string $cle): string
    {
        return match ($cle) {
            'en_attente' => 'fas fa-clock',
            'confirme' => 'fas fa-user-check',
            'en_cours' => 'fas fa-user-md',
            'termine' => 'fas fa-check-double',
            'annule' => 'fas fa-times',
            'consultation' => 'fas fa-stethoscope',
            default => 'fas fa-clock',
        };
    }
}
