<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Rendezvou;
use App\Models\Medecin;
use Illuminate\Support\Facades\Auth;

class SalleAttente extends Component
{
    public $date;
    public $medecinFiltre = null;
    public $medecins = [];
    public bool $modeMobile = false;

    protected $listeners = [
        'refresh' => '$refresh',
    ];

    public function mount($modeMobile = false)
    {
        $this->modeMobile = $modeMobile;
        $this->date = now()->format('Y-m-d');
        $this->loadMedecins();

        // Si c'est un médecin simple, forcer le filtre sur lui
        $user = Auth::user();
        if ($user->isDocteur() && !$user->isDocteurProprietaire()) {
            $this->medecinFiltre = $user->fkidmedecin;
        }
    }

    private function loadMedecins()
    {
        $user = Auth::user();
        $query = Medecin::orderBy('Nom');

        if ($user->isDocteur() && !$user->isDocteurProprietaire()) {
            $query->where('idMedecin', $user->fkidmedecin);
        }

        $this->medecins = $query->get();
    }

    public function selectionnerPatient($patientData)
    {
        $this->emit('patientSelectedFromSalle', $patientData);
    }

    public function changerStatut($rdvId, $statut)
    {
        $rdv = Rendezvou::find($rdvId);
        if ($rdv) {
            $rdv->rdvConfirmer = $statut;
            $rdv->save();
        }
    }

    // Appeler quand on clique sur un RDV en attente/confirmé :
    // termine le RDV "En cours" du même médecin, puis passe ce RDV "En cours"
    public function demarrerRdv($rdvId)
    {
        $rdv = Rendezvou::find($rdvId);
        if (!$rdv) return;

        // Terminer tout RDV "En cours" du même médecin ce jour
        Rendezvou::where('fkidMedecin', $rdv->fkidMedecin)
            ->whereDate('dtPrevuRDV', $this->date)
            ->where('rdvConfirmer', 'En cours')
            ->where('IDRdv', '!=', $rdvId)
            ->update(['rdvConfirmer' => 'Terminé']);

        $rdv->rdvConfirmer = 'En cours';
        $rdv->save();
    }

    public function terminerRdv($rdvId)
    {
        $rdv = Rendezvou::find($rdvId);
        if ($rdv) {
            $rdv->rdvConfirmer = 'Terminé';
            $rdv->save();
        }
    }

    public function getRendezVousProperty()
    {
        $query = Rendezvou::with(['patient', 'medecin'])
            ->whereDate('dtPrevuRDV', $this->date)
            ->whereNotIn('rdvConfirmer', ['Annulé', 'annulé', 'Terminé', 'terminé'])
            ->where('fkidcabinet', Auth::user()->fkidcabinet)
            ->orderBy('HeureRdv');

        if ($this->medecinFiltre) {
            $query->where('fkidMedecin', $this->medecinFiltre);
        }

        return $query->get()->groupBy('fkidMedecin');
    }

    public function render()
    {
        return view($this->modeMobile ? 'livewire.salle-attente-mobile' : 'livewire.salle-attente', [
            'rdvParMedecin' => $this->rendezVous,
        ]);
    }
}
