<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Models\Concerns\BelongsToTenant;
use App\Exceptions\RdvConflictException;

class Rendezvou extends Model
{
    use BelongsToTenant;

    protected static $tenantColumn = 'fkidcabinet';

    // Nom de la table
    protected $table = 'rendezvous';

    // Clé primaire
    protected $primaryKey = 'IDRdv';

    // Pas de timestamps Laravel (created_at, updated_at)
    public $timestamps = false;

    // Champs à caster en date/heure
    protected $casts = [
        'DtAjRdv' => 'datetime',
        'dtPrevuRDV' => 'datetime',
        'HeureRdv' => 'datetime',
        'HeureConfRDV' => 'datetime',
    ];

    // Champs assignables en masse
    protected $fillable = [
        'ActePrevu',
        'DtAjRdv',
        'dtPrevuRDV',
        'user',
        'HeureRdv',
        'fkidPatient',
        'rdvConfirmer',
        'fkidMedecin',
        'OrdreRDV',
        'HeureConfRDV',
        'fkidcabinet',
        'fkidFacture',
        'date_dernier_rappel'
    ];

    /**
     * Relation : le médecin associé à ce rendez-vous
     */
    public function medecin()
    {
        return $this->belongsTo(Medecin::class, 'fkidMedecin', 'idMedecin');
    }

    /**
     * Relation : le patient associé à ce rendez-vous
     */
    public function patient()
    {
        return $this->belongsTo(Patient::class, 'fkidPatient', 'ID');
    }

    /**
     * Relation : le cabinet associé à ce rendez-vous
     */
    public function cabinet()
    {
        return $this->belongsTo(Infocabinet::class, 'fkidcabinet', 'idEntete');
    }

    /**
     * Résout le cabinet à utiliser pour une opération de RDV : l'ID explicite
     * s'il est fourni (contexte non authentifié, ex. portail patient public),
     * sinon l'utilisateur connecté (contexte staff). Lève une exception plutôt
     * que d'exécuter une requête non filtrée par tenant si aucun des deux
     * n'est disponible — TenantScope ne filtre pas automatiquement quand
     * Auth::check() est faux (cf. app/Models/Scopes/TenantScope.php).
     */
    protected static function resolveCabinetId($cabinetId = null): int
    {
        $cabinetId = $cabinetId ?? (Auth::check() ? Auth::user()->fkidcabinet : null);

        if (!$cabinetId) {
            throw new \InvalidArgumentException('Impossible de déterminer le cabinet pour cette opération de rendez-vous.');
        }

        return (int) $cabinetId;
    }

    /**
     * Génère le prochain numéro d'ordre pour la date et le médecin donnés
     * Utilise un verrou pour éviter les conditions de course
     */
    public static function generateNextOrderNumber($date, $medecinId = null, $cabinetId = null)
    {
        if (is_string($date)) {
            $date = \Carbon\Carbon::parse($date);
        }

        $cabinetId = self::resolveCabinetId($cabinetId);

        // Utiliser une transaction avec verrou pour éviter les conditions de course
        return \DB::transaction(function () use ($date, $medecinId, $cabinetId) {
            $query = self::select('OrdreRDV')
                ->whereDate('dtPrevuRDV', $date->format('Y-m-d'))
                ->where('fkidcabinet', $cabinetId);

            // Si un médecin est spécifié, filtrer par médecin
            if ($medecinId) {
                $query->where('fkidMedecin', $medecinId);
            }

            // Utiliser lockForUpdate pour éviter les conditions de course
            $lastOrder = $query->lockForUpdate()->max('OrdreRDV');

            return ($lastOrder ?? 0) + 1;
        });
    }

    /**
     * Met à jour le statut d'un rendez-vous et gère automatiquement les conflits
     * @param int $rdvId ID du rendez-vous
     * @param string $nouveauStatut Nouveau statut
     * @return array ['success' => bool, 'message' => string]
     */
    public static function updateStatusWithConflictManagement($rdvId, $nouveauStatut)
    {
        try {
            $rdv = self::find($rdvId);
            
            if (!$rdv) {
                return ['success' => false, 'message' => 'Rendez-vous non trouvé.'];
            }

            // Si le nouveau statut est "En cours", terminer automatiquement les autres patients
            if ($nouveauStatut === 'En cours') {
                // Terminer tous les autres patients "En cours" du même médecin pour la même journée
                $patientsTermines = self::where('fkidMedecin', $rdv->fkidMedecin)
                    ->whereDate('dtPrevuRDV', $rdv->dtPrevuRDV)
                    ->where('IDRdv', '!=', $rdv->IDRdv)
                    ->where('rdvConfirmer', 'En cours')
                    ->update(['rdvConfirmer' => 'Terminé']);
                
                $message = 'Statut mis à jour.';
                if ($patientsTermines > 0) {
                    $message .= " {$patientsTermines} autre(s) patient(s) en cours ont été automatiquement terminé(s).";
                }
            } else {
                $message = 'Statut du rendez-vous mis à jour avec succès.';
            }

            $updateData = ['rdvConfirmer' => $nouveauStatut];
            
            // Si on confirme, ajouter l'heure de confirmation
            if ($nouveauStatut === 'Confirmé') {
                $updateData['HeureConfRDV'] = now();
            }
            
            $rdv->update($updateData);
            
            return ['success' => true, 'message' => $message];
            
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Erreur lors de la modification du statut: ' . $e->getMessage()];
        }
    }

    /**
     * Vérifie s'il y a un conflit d'horaire pour un médecin à une date/heure donnée
     * @param int $medecinId ID du médecin
     * @param string $date Date du rendez-vous (Y-m-d)
     * @param string $heure Heure du rendez-vous (H:i)
     * @param int|null $excludeRdvId ID du rendez-vous à exclure (pour les modifications)
     * @return bool True s'il y a un conflit
     */
    public static function hasConflict($medecinId, $date, $heure, $excludeRdvId = null, $cabinetId = null)
    {
        $cabinetId = self::resolveCabinetId($cabinetId);

        // Convertir la date et l'heure en datetime
        $dateTimeRdv = Carbon::parse($date . ' ' . $heure);

        // Durée d'un rendez-vous, configurable par cabinet (défaut 10 minutes)
        $dureeRdv = Infocabinet::find($cabinetId)->duree_rdv_minutes ?? 10;

        // Calculer l'heure de fin du rendez-vous
        $heureFin = $dateTimeRdv->copy()->addMinutes($dureeRdv);

        // Chercher les rendez-vous existants pour ce médecin à cette date
        $query = self::where('fkidMedecin', $medecinId)
            ->whereDate('dtPrevuRDV', $date)
            ->whereNotIn('rdvConfirmer', ['Annulé', 'annulé'])
            ->where('fkidcabinet', $cabinetId);
        
        // Exclure le rendez-vous en cours de modification
        if ($excludeRdvId) {
            $query->where('IDRdv', '!=', $excludeRdvId);
        }
        
        $rendezVousExistants = $query->get();
        
        foreach ($rendezVousExistants as $rdv) {
            if (!$rdv->HeureRdv) continue;

            // HeureRdv est stockée en base comme une heure seule (H:i) : la
            // recombiner avec la date du RDV existant plutôt que de la
            // parser seule, qui résoudrait implicitement à la date du jour.
            $heureDebutExistant = Carbon::parse($date . ' ' . Carbon::parse($rdv->HeureRdv)->format('H:i'));
            $heureFinExistant = $heureDebutExistant->copy()->addMinutes($dureeRdv);
            
            // Vérifier s'il y a un chevauchement
            if ($dateTimeRdv < $heureFinExistant && $heureFin > $heureDebutExistant) {
                return true; // Conflit détecté
            }
        }
        
        return false; // Aucun conflit
    }

    /**
     * Récupère les créneaux disponibles pour un médecin à une date donnée
     * @param int $medecinId ID du médecin
     * @param string $date Date (Y-m-d)
     * @return array Liste des créneaux disponibles
     */
    public static function getCreneauxDisponibles($medecinId, $date, $cabinetId = null)
    {
        $cabinetId = self::resolveCabinetId($cabinetId);
        $cabinet = Infocabinet::find($cabinetId);

        $heureDebut = $cabinet && $cabinet->heure_ouverture ? (int) Carbon::parse($cabinet->heure_ouverture)->format('H') : 8;
        $heureFin = $cabinet && $cabinet->heure_fermeture ? (int) Carbon::parse($cabinet->heure_fermeture)->format('H') : 18;
        $dureeCreneau = $cabinet->duree_rdv_minutes ?? 10;

        $creneauxDisponibles = [];

        for ($heure = $heureDebut; $heure < $heureFin; $heure++) {
            for ($minute = 0; $minute < 60; $minute += $dureeCreneau) {
                $heureCreneau = sprintf('%02d:%02d', $heure, $minute);

                // Vérifier s'il y a un conflit pour ce créneau
                if (!self::hasConflict($medecinId, $date, $heureCreneau, null, $cabinetId)) {
                    $creneauxDisponibles[] = $heureCreneau;
                }
            }
        }

        return $creneauxDisponibles;
    }

    /**
     * Propose le prochain créneau disponible après le dernier rendez-vous
     * @param int $medecinId ID du médecin
     * @param string $date Date (Y-m-d)
     * @return string|null Heure proposée ou null si aucun créneau disponible
     */
    public static function getProchainCreneauPropose($medecinId, $date, $cabinetId = null)
    {
        $cabinetId = self::resolveCabinetId($cabinetId);
        $cabinet = Infocabinet::find($cabinetId);

        $heureOuverture = $cabinet && $cabinet->heure_ouverture ? Carbon::parse($cabinet->heure_ouverture)->format('H:i') : '08:00';
        $heureFermeture = $cabinet && $cabinet->heure_fermeture ? (int) Carbon::parse($cabinet->heure_fermeture)->format('H') : 18;
        $dureeRdv = $cabinet->duree_rdv_minutes ?? 10;

        // Récupérer le dernier rendez-vous du médecin pour cette date
        $dernierRdv = self::where('fkidMedecin', $medecinId)
            ->whereDate('dtPrevuRDV', $date)
            ->whereNotIn('rdvConfirmer', ['Annulé', 'annulé'])
            ->where('fkidcabinet', $cabinetId)
            ->orderBy('HeureRdv', 'desc')
            ->first();

        if (!$dernierRdv || !$dernierRdv->HeureRdv) {
            // Aucun RDV existant, proposer l'heure d'ouverture
            return $heureOuverture;
        }

        // Calculer le prochain créneau (durée configurée après la fin du dernier RDV)
        $heureFinDernierRdv = Carbon::parse($dernierRdv->HeureRdv)->addMinutes($dureeRdv);

        // Vérifier que l'heure proposée est dans les heures de travail
        if ($heureFinDernierRdv->hour >= $heureFermeture) {
            return null; // Plus de créneaux disponibles aujourd'hui
        }

        return $heureFinDernierRdv->format('H:i');
    }

    /**
     * Crée un rendez-vous de façon atomique : verrouille les RDV actifs du
     * médecin/jour, revalide l'absence de conflit à l'intérieur du verrou
     * (hasConflict() seul n'a aucune protection contre la concurrence),
     * calcule le numéro d'ordre, puis crée le RDV. Réutilisée par le flux
     * staff (CreateRendezVous) et le flux patient self-service.
     */
    public static function createWithLock(array $data, $cabinetId): self
    {
        $cabinetId = self::resolveCabinetId($cabinetId);

        return \DB::transaction(function () use ($data, $cabinetId) {
            self::where('fkidMedecin', $data['fkidMedecin'])
                ->whereDate('dtPrevuRDV', $data['dtPrevuRDV'])
                ->where('fkidcabinet', $cabinetId)
                ->lockForUpdate()
                ->get();

            if (self::hasConflict($data['fkidMedecin'], $data['dtPrevuRDV'], $data['HeureRdv'], null, $cabinetId)) {
                throw new RdvConflictException('Ce créneau vient d\'être réservé par un autre rendez-vous.');
            }

            $data['fkidcabinet'] = $cabinetId;
            $data['OrdreRDV'] = self::generateNextOrderNumber($data['dtPrevuRDV'], $data['fkidMedecin'], $cabinetId);

            return self::create($data);
        });
    }
} 