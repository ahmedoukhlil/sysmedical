<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Rendezvou;
use App\Models\Medecin;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class AgendaSemaine extends Component
{
    public $semaineDebut;
    public $medecinFiltre = null;
    public $medecins = [];

    protected $listeners = [
        'refresh' => '$refresh',
    ];

    public function mount()
    {
        $this->semaineDebut = now()->startOfWeek()->format('Y-m-d');
        $this->loadMedecins();

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

    public function semainePrecedente()
    {
        $this->semaineDebut = Carbon::parse($this->semaineDebut)->subWeek()->format('Y-m-d');
    }

    public function semaineSuivante()
    {
        $this->semaineDebut = Carbon::parse($this->semaineDebut)->addWeek()->format('Y-m-d');
    }

    public function semaineActuelle()
    {
        $this->semaineDebut = now()->startOfWeek()->format('Y-m-d');
    }

    // Copie assumée (pas d'héritage) de la logique de SalleAttente::demarrerRdv/terminerRdv.
    // Si un 3e composant a besoin de la même logique, extraire en trait HandlesRdvLifecycle.
    public function demarrerRdv($rdvId)
    {
        $rdv = Rendezvou::find($rdvId);
        if (!$rdv) return;

        Rendezvou::where('fkidMedecin', $rdv->fkidMedecin)
            ->whereDate('dtPrevuRDV', $rdv->dtPrevuRDV)
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

    public function getJoursSemaineProperty()
    {
        $debut = Carbon::parse($this->semaineDebut);
        $jours = [];

        for ($i = 0; $i < 7; $i++) {
            $jour = $debut->copy()->addDays($i);

            $query = Rendezvou::with(['patient', 'medecin'])
                ->whereDate('dtPrevuRDV', $jour->format('Y-m-d'))
                ->whereNotIn('rdvConfirmer', ['Annulé', 'annulé'])
                ->where('fkidcabinet', Auth::user()->fkidcabinet)
                ->orderBy('HeureRdv');

            if ($this->medecinFiltre) {
                $query->where('fkidMedecin', $this->medecinFiltre);
            }

            $jours[] = ['date' => $jour, 'rdvs' => $query->get()];
        }

        return collect($jours);
    }

    public function render()
    {
        return view('livewire.agenda-semaine', [
            'jours' => $this->joursSemaine,
        ]);
    }
}
