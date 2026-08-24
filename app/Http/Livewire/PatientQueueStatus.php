<?php

namespace App\Http\Livewire;

use App\Models\Patient;
use App\Models\Rendezvou;
use Livewire\Component;

class PatientQueueStatus extends Component
{
    public $token;

    public $patient = null;
    public $prochainRdv = null;
    public $rendezVousMedecinJournee;
    public $positionPatient = null;
    public $tempsAttenteEstime = null;
    public $patientEnCours = null;
    public $positionPatientEnCours = null;
    public $patientsAvantMoi = 0;
    public $estAujourdhui = false;
    public $estFutur = false;
    public $estPasse = false;

    public function mount($token)
    {
        $this->token = $token;
        $this->rendezVousMedecinJournee = collect();
        $this->refresh();
    }

    public function refresh()
    {
        $patientId = $this->decodeToken($this->token);

        if (!$patientId) {
            return;
        }

        $dateToken = $this->getDateFromToken($this->token);
        $medecinIdFromToken = $this->getMedecinIdFromToken($this->token);

        $this->patient = Patient::find($patientId);

        if (!$this->patient) {
            return;
        }

        $query = Rendezvou::with(['medecin', 'cabinet'])
            ->where('fkidPatient', $patientId)
            ->whereDate('dtPrevuRDV', $dateToken)
            ->whereNotIn('rdvConfirmer', ['Annulé', 'annulé']);

        if ($medecinIdFromToken) {
            $query->where('fkidMedecin', $medecinIdFromToken);
        }

        $this->prochainRdv = $query->orderBy('OrdreRDV', 'asc')->first();

        $this->rendezVousMedecinJournee = collect();
        $this->estAujourdhui = false;
        $this->estFutur = false;
        $this->estPasse = false;
        $this->positionPatient = null;
        $this->tempsAttenteEstime = null;
        $this->patientEnCours = null;
        $this->positionPatientEnCours = null;
        $this->patientsAvantMoi = 0;

        if (!$this->prochainRdv) {
            return;
        }

        $dateAujourdhui = now()->format('Y-m-d');
        $dateRdv = $this->prochainRdv->dtPrevuRDV;
        $dateRdvFormatted = is_string($dateRdv) ? $dateRdv : $dateRdv->format('Y-m-d');

        $this->estAujourdhui = ($dateRdvFormatted === $dateAujourdhui);
        $this->estFutur = ($dateRdvFormatted > $dateAujourdhui);
        $this->estPasse = ($dateRdvFormatted < $dateAujourdhui);

        if (!$this->estAujourdhui) {
            return;
        }

        $this->rendezVousMedecinJournee = Rendezvou::with(['patient', 'medecin'])
            ->where('fkidMedecin', $this->prochainRdv->fkidMedecin)
            ->whereDate('dtPrevuRDV', $dateToken)
            ->whereNotIn('rdvConfirmer', ['Annulé', 'annulé'])
            ->orderBy('OrdreRDV', 'asc')
            ->get();

        $this->positionPatient = $this->prochainRdv->OrdreRDV;

        $prochainRdv = $this->prochainRdv;
        $this->patientsAvantMoi = $this->rendezVousMedecinJournee->filter(function ($rdv) use ($prochainRdv) {
            return $rdv->OrdreRDV < $prochainRdv->OrdreRDV
                && !in_array($rdv->rdvConfirmer, ['Terminé', 'terminé']);
        })->count();

        $this->tempsAttenteEstime = $this->patientsAvantMoi * 15;

        $this->patientEnCours = $this->rendezVousMedecinJournee->first(function ($rdv) {
            return $rdv->rdvConfirmer == 'En cours';
        });

        if ($this->patientEnCours) {
            $this->positionPatientEnCours = $this->patientEnCours->OrdreRDV;
        }
    }

    private function decodeTokenParts($token)
    {
        if (substr_count($token, '.') === 1) {
            [$encodedPayload, $encodedSig] = explode('.', $token, 2);

            $expectedSig = hash_hmac('sha256', $encodedPayload, config('app.key'));
            $expectedEnc = rtrim(strtr(base64_encode($expectedSig), '+/', '-_'), '=');

            if (!hash_equals($expectedEnc, $encodedSig)) {
                return null;
            }

            $payload = base64_decode(strtr($encodedPayload, '-_', '+/'));
            $parts = explode('|', $payload);
            return count($parts) >= 2 ? $parts : null;
        }

        $decoded = base64_decode($token, true);
        if ($decoded === false) {
            return null;
        }
        $parts = explode('|', $decoded);
        return count($parts) >= 2 ? $parts : null;
    }

    private function getDateFromToken($token)
    {
        $parts = $this->decodeTokenParts($token);
        return ($parts && isset($parts[1])) ? $parts[1] : date('Y-m-d');
    }

    private function getMedecinIdFromToken($token)
    {
        $parts = $this->decodeTokenParts($token);
        return ($parts && isset($parts[2])) ? $parts[2] : null;
    }

    private function decodeToken($token)
    {
        try {
            $parts = $this->decodeTokenParts($token);
            if ($parts) {
                return $parts[0];
            }

            $dateToken = base64_decode($token, true);
            $dateDuJour = date('Y-m-d');
            if ($dateToken === $dateDuJour) {
                $rdv = Rendezvou::whereDate('dtPrevuRDV', $dateDuJour)->first();
                return $rdv ? $rdv->fkidPatient : null;
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function render()
    {
        return view('livewire.patient-queue-status');
    }
}
