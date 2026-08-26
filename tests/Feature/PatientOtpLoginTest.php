<?php

namespace Tests\Feature;

use App\Http\Livewire\PatientOtpLogin;
use App\Models\Patient;
use App\Services\PatientTokenService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\Feature\Concerns\CreatesTenantFixtures;
use Tests\TestCase;

class PatientOtpLoginTest extends TestCase
{
    use DatabaseTransactions;
    use CreatesTenantFixtures;

    public function test_existing_phone_number_generates_otp_and_dispatches_whatsapp_job()
    {
        Queue::fake();

        [$cabinet] = $this->makeCabinetWithUser(16001, 'userOtp1');
        Patient::create(['Nom' => 'PatientOtp1', 'Telephone1' => '22211111111', 'fkidcabinet' => $cabinet->idEntete]);

        $cabinetToken = app(PatientTokenService::class)->generateCabinetToken($cabinet->idEntete);

        Livewire::test(PatientOtpLogin::class, ['cabinetToken' => $cabinetToken])
            ->set('telephone', '22211111111')
            ->call('requestOtp')
            ->assertSet('step', 'otp');

        Queue::assertPushed(\App\Jobs\SendWhatsAppMessage::class);
    }

    public function test_unknown_phone_number_shows_generic_message_without_leaking_existence()
    {
        [$cabinet] = $this->makeCabinetWithUser(16002, 'userOtp2');

        $cabinetToken = app(PatientTokenService::class)->generateCabinetToken($cabinet->idEntete);

        Livewire::test(PatientOtpLogin::class, ['cabinetToken' => $cabinetToken])
            ->set('telephone', '22299999999')
            ->call('requestOtp')
            ->assertSet('step', 'phone')
            ->assertSet('errorMessage', 'Numéro non reconnu. Merci de contacter le cabinet.');
    }

    public function test_correct_otp_code_generates_valid_booking_ticket()
    {
        [$cabinet] = $this->makeCabinetWithUser(16003, 'userOtp3');
        $patient = Patient::create(['Nom' => 'PatientOtp3', 'Telephone1' => '22233333333', 'fkidcabinet' => $cabinet->idEntete]);

        Cache::put("otp:{$cabinet->idEntete}:{$patient->ID}", ['code' => Hash::make('123456'), 'attempts' => 0], now()->addMinutes(10));

        $cabinetToken = app(PatientTokenService::class)->generateCabinetToken($cabinet->idEntete);

        $component = Livewire::test(PatientOtpLogin::class, ['cabinetToken' => $cabinetToken]);
        $component->set('patientId', $patient->ID);
        $component->set('code', '123456')->call('verifyOtp');

        $component->assertSet('step', 'booking');
        $ticket = $component->get('bookingTicket');
        $this->assertNotNull($ticket);

        $context = app(PatientTokenService::class)->verifyBookingTicket($ticket);
        $this->assertEquals($patient->ID, $context['patientId']);
        $this->assertEquals($cabinet->idEntete, $context['cabinetId']);
    }

    public function test_incorrect_otp_code_five_times_blocks_and_resets()
    {
        [$cabinet] = $this->makeCabinetWithUser(16004, 'userOtp4');
        $patient = Patient::create(['Nom' => 'PatientOtp4', 'Telephone1' => '22244444444', 'fkidcabinet' => $cabinet->idEntete]);

        Cache::put("otp:{$cabinet->idEntete}:{$patient->ID}", ['code' => Hash::make('123456'), 'attempts' => 0], now()->addMinutes(10));

        $cabinetToken = app(PatientTokenService::class)->generateCabinetToken($cabinet->idEntete);
        $component = Livewire::test(PatientOtpLogin::class, ['cabinetToken' => $cabinetToken]);
        $component->set('patientId', $patient->ID);

        for ($i = 0; $i < 5; $i++) {
            $component->set('code', '000000')->call('verifyOtp');
        }

        $component->assertSet('step', 'phone');
        $this->assertNull(Cache::get("otp:{$cabinet->idEntete}:{$patient->ID}"));
    }

    public function test_expired_otp_code_is_refused()
    {
        [$cabinet] = $this->makeCabinetWithUser(16005, 'userOtp5');
        $patient = Patient::create(['Nom' => 'PatientOtp5', 'Telephone1' => '22255555555', 'fkidcabinet' => $cabinet->idEntete]);

        // Ne pas stocker de code en cache = équivalent à une expiration du TTL.
        $cabinetToken = app(PatientTokenService::class)->generateCabinetToken($cabinet->idEntete);
        $component = Livewire::test(PatientOtpLogin::class, ['cabinetToken' => $cabinetToken]);
        $component->set('patientId', $patient->ID);

        $component->set('code', '123456')->call('verifyOtp');

        $component->assertSet('step', 'phone');
        $component->assertSet('errorMessage', 'Le code a expiré. Merci de recommencer.');
    }

    public function test_too_many_otp_requests_are_blocked()
    {
        [$cabinet] = $this->makeCabinetWithUser(16006, 'userOtp6');
        Patient::create(['Nom' => 'PatientOtp6', 'Telephone1' => '22266666666', 'fkidcabinet' => $cabinet->idEntete]);

        $cabinetToken = app(PatientTokenService::class)->generateCabinetToken($cabinet->idEntete);

        for ($i = 0; $i < 5; $i++) {
            \DB::table('patient_otp_attempts')->insert([
                'telephone' => '22266666666',
                'fkidcabinet' => $cabinet->idEntete,
                'ip' => '127.0.0.1',
                'created_at' => now(),
            ]);
        }

        Livewire::test(PatientOtpLogin::class, ['cabinetToken' => $cabinetToken])
            ->set('telephone', '22266666666')
            ->call('requestOtp')
            ->assertSet('errorMessage', 'Trop de tentatives. Merci de réessayer dans quelques minutes.');
    }
}
