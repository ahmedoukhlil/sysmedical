<?php

namespace App\Http\Livewire;

use App\Exceptions\RdvConflictException;
use App\Jobs\SendWhatsAppMessage;
use App\Models\Medecin;
use App\Models\Patient;
use App\Models\Rendezvou;
use App\Services\PatientTokenService;
use App\Support\RdvMessageFormatter;
use Carbon\Carbon;
use Livewire\Component;

class PatientBookingCalendar extends Component
{
    public string $ticket;

    public array $medecins = [];
    public ?int $medecinId = null;
    public string $date = '';
    public array $creneaux = [];
    public ?string $heureChoisie = null;

    public bool $confirme = false;
    public string $errorMessage = '';

    public function mount(string $ticket)
    {
        $this->ticket = $ticket;

        $context = $this->verifiedContext();

        if ($context === null) {
            $this->errorMessage = 'Session expirée. Merci de recommencer.';
            return;
        }

        $this->date = now()->format('Y-m-d');
        $this->medecins = Medecin::where('fkidcabinet', $context['cabinetId'])
            ->orderBy('Nom')
            ->get(['idMedecin', 'Nom'])
            ->toArray();
    }

    public function updatedMedecinId()
    {
        $this->loadCreneaux();
    }

    public function updatedDate()
    {
        $this->loadCreneaux();
    }

    public function loadCreneaux()
    {
        $this->errorMessage = '';
        $this->creneaux = [];

        $context = $this->verifiedContext();
        if ($context === null || !$this->medecinId) {
            return;
        }

        if (Carbon::parse($this->date)->gt(now()->addDays(config('booking.max_advance_days', 30)))) {
            $this->errorMessage = 'Cette date est trop éloignée pour une réservation en ligne.';
            return;
        }

        $this->creneaux = Rendezvou::getCreneauxDisponibles($this->medecinId, $this->date, $context['cabinetId']);
    }

    public function confirmerRdv()
    {
        $this->errorMessage = '';

        $context = $this->verifiedContext();
        if ($context === null) {
            $this->errorMessage = 'Session expirée. Merci de recommencer.';
            return;
        }

        if (!$this->medecinId || !$this->heureChoisie) {
            $this->errorMessage = 'Merci de choisir un médecin et un créneau.';
            return;
        }

        $upcomingCount = Rendezvou::where('fkidPatient', $context['patientId'])
            ->where('fkidcabinet', $context['cabinetId'])
            ->where('dtPrevuRDV', '>=', now()->format('Y-m-d'))
            ->whereNotIn('rdvConfirmer', ['Annulé', 'annulé'])
            ->count();

        if ($upcomingCount >= config('booking.max_upcoming_rdv_per_patient', 3)) {
            $this->errorMessage = 'Vous avez déjà le nombre maximum de rendez-vous à venir. Merci de contacter le cabinet.';
            return;
        }

        try {
            $rdv = Rendezvou::createWithLock([
                'fkidPatient' => $context['patientId'],
                'fkidMedecin' => $this->medecinId,
                'dtPrevuRDV' => $this->date,
                'HeureRdv' => $this->heureChoisie,
                'ActePrevu' => 'Consultation',
                'rdvConfirmer' => 'En Attente',
            ], $context['cabinetId']);
        } catch (RdvConflictException $e) {
            $this->loadCreneaux();
            $this->errorMessage = $e->getMessage();
            return;
        }

        $rdv->load(['patient', 'medecin']);

        $patient = Patient::find($context['patientId']);
        if ($patient && $patient->Telephone1) {
            SendWhatsAppMessage::dispatch($patient->Telephone1, RdvMessageFormatter::formatConfirmation($rdv));
        }

        $this->confirme = true;
    }

    private function verifiedContext(): ?array
    {
        return app(PatientTokenService::class)->verifyBookingTicket($this->ticket);
    }

    public function render()
    {
        return view('livewire.patient-booking-calendar');
    }
}
