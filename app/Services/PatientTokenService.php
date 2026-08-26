<?php

namespace App\Services;

class PatientTokenService
{
    /**
     * Token public par cabinet (affichable en QR code/affiche), encode
     * uniquement idEntete. Signé, sans expiration (usage statique et durable
     * par cabinet). Distinct des tokens PatientInterfaceController::generateToken()
     * qui servent au suivi d'un RDV existant - ne pas fusionner les deux
     * mécanismes pour ne pas risquer de régression sur celui déjà en prod.
     */
    public function generateCabinetToken(int $cabinetId): string
    {
        return $this->sign((string) $cabinetId);
    }

    public function verifyCabinetToken(string $token): ?int
    {
        $payload = $this->verify($token);

        return $payload !== null ? (int) $payload : null;
    }

    /**
     * Ticket de session délivré après validation OTP, encode patientId|cabinetId|expiration.
     * Revalidé (signature + expiration) à chaque action du composant de réservation -
     * jamais de confiance dans la seule présence en session Livewire.
     */
    public function generateBookingTicket(int $patientId, int $cabinetId, int $ttlMinutes = 15): string
    {
        $expiration = now()->addMinutes($ttlMinutes)->timestamp;
        $payload = "{$patientId}|{$cabinetId}|{$expiration}";

        return $this->sign($payload);
    }

    /**
     * Retourne ['patientId' => int, 'cabinetId' => int] si le ticket est valide
     * et non expiré, sinon null.
     */
    public function verifyBookingTicket(string $ticket): ?array
    {
        $payload = $this->verify($ticket);

        if ($payload === null) {
            return null;
        }

        $parts = explode('|', $payload);

        if (count($parts) !== 3) {
            return null;
        }

        [$patientId, $cabinetId, $expiration] = $parts;

        if ((int) $expiration < now()->timestamp) {
            return null;
        }

        return ['patientId' => (int) $patientId, 'cabinetId' => (int) $cabinetId];
    }

    private function sign(string $payload): string
    {
        $encodedPayload = rtrim(strtr(base64_encode($payload), '+/', '-_'), '=');
        $signature = hash_hmac('sha256', $encodedPayload, config('app.key'));
        $encodedSig = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

        return $encodedPayload . '.' . $encodedSig;
    }

    private function verify(string $token): ?string
    {
        if (substr_count($token, '.') !== 1) {
            return null;
        }

        [$encodedPayload, $encodedSig] = explode('.', $token, 2);

        $expectedSig = hash_hmac('sha256', $encodedPayload, config('app.key'));
        $expectedEnc = rtrim(strtr(base64_encode($expectedSig), '+/', '-_'), '=');

        if (!hash_equals($expectedEnc, $encodedSig)) {
            return null;
        }

        $decoded = base64_decode(strtr($encodedPayload, '-_', '+/'));

        return $decoded !== false ? $decoded : null;
    }
}
