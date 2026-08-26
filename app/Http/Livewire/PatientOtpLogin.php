<?php

namespace App\Http\Livewire;

use App\Jobs\SendWhatsAppMessage;
use App\Models\Infocabinet;
use App\Models\Patient;
use App\Services\PatientTokenService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class PatientOtpLogin extends Component
{
    public string $cabinetToken;
    public int $cabinetId;
    public bool $tokenInvalid = false;

    public string $step = 'phone'; // phone | otp | booking

    public string $telephone = '';
    public string $code = '';

    public ?int $patientId = null;
    public ?string $bookingTicket = null;

    public string $errorMessage = '';

    public function mount(string $cabinetToken)
    {
        $this->cabinetToken = $cabinetToken;

        $cabinetId = app(PatientTokenService::class)->verifyCabinetToken($cabinetToken);

        if ($cabinetId === null || !Infocabinet::find($cabinetId)) {
            $this->tokenInvalid = true;
            return;
        }

        $this->cabinetId = $cabinetId;
    }

    public function requestOtp()
    {
        $this->errorMessage = '';

        $this->validate([
            'telephone' => 'required|string|min:6',
        ]);

        if ($this->tooManyAttempts()) {
            $this->errorMessage = 'Trop de tentatives. Merci de réessayer dans quelques minutes.';
            return;
        }

        $patient = Patient::where('fkidcabinet', $this->cabinetId)
            ->where(function ($q) {
                $q->where('Telephone1', $this->telephone)
                  ->orWhere('Telephone2', $this->telephone);
            })
            ->first();

        $this->recordAttempt();

        if (!$patient) {
            $this->errorMessage = 'Numéro non reconnu. Merci de contacter le cabinet.';
            return;
        }

        $this->patientId = $patient->ID;

        $code = (string) random_int(100000, 999999);

        Cache::put(
            "otp:{$this->cabinetId}:{$patient->ID}",
            ['code' => Hash::make($code), 'attempts' => 0],
            now()->addMinutes(10)
        );

        SendWhatsAppMessage::dispatch(
            $this->telephone,
            "Votre code de vérification pour prendre rendez-vous est : {$code}\nCe code expire dans 10 minutes."
        );

        $this->step = 'otp';
    }

    public function verifyOtp()
    {
        $this->errorMessage = '';

        $this->validate([
            'code' => 'required|digits:6',
        ]);

        $cacheKey = "otp:{$this->cabinetId}:{$this->patientId}";
        $entry = Cache::get($cacheKey);

        if (!$entry) {
            $this->errorMessage = 'Le code a expiré. Merci de recommencer.';
            $this->step = 'phone';
            return;
        }

        if ($entry['attempts'] >= 5) {
            Cache::forget($cacheKey);
            $this->errorMessage = 'Trop de tentatives incorrectes. Merci de recommencer.';
            $this->step = 'phone';
            return;
        }

        if (!Hash::check($this->code, $entry['code'])) {
            $entry['attempts']++;

            if ($entry['attempts'] >= 5) {
                Cache::forget($cacheKey);
                $this->errorMessage = 'Trop de tentatives incorrectes. Merci de recommencer.';
                $this->step = 'phone';
                return;
            }

            Cache::put($cacheKey, $entry, now()->addMinutes(10));
            $this->errorMessage = 'Code incorrect.';
            return;
        }

        Cache::forget($cacheKey);

        $this->bookingTicket = app(PatientTokenService::class)->generateBookingTicket($this->patientId, $this->cabinetId);
        $this->step = 'booking';
    }

    private function tooManyAttempts(): bool
    {
        $window = now()->subMinutes(15);

        $byPhone = DB::table('patient_otp_attempts')
            ->where('telephone', $this->telephone)
            ->where('fkidcabinet', $this->cabinetId)
            ->where('created_at', '>=', $window)
            ->count();

        $byIp = DB::table('patient_otp_attempts')
            ->where('ip', request()->ip())
            ->where('created_at', '>=', $window)
            ->count();

        return $byPhone >= 5 || $byIp >= 10;
    }

    private function recordAttempt(): void
    {
        DB::table('patient_otp_attempts')->insert([
            'telephone' => $this->telephone,
            'fkidcabinet' => $this->cabinetId,
            'ip' => request()->ip(),
            'created_at' => now(),
        ]);
    }

    public function render()
    {
        return view('livewire.patient-otp-login');
    }
}
