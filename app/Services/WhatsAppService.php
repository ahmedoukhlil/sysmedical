<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    /**
     * Envoie un message WhatsApp via Meta Cloud API. Si aucun credential n'est
     * configuré, journalise le message au lieu de l'envoyer (mode dry-run) afin
     * de permettre de tester tout le flux avant qu'un compte Meta réel n'existe.
     */
    public function send(string $toPhoneE164, string $message): bool
    {
        $token = config('services.whatsapp.token');
        $phoneNumberId = config('services.whatsapp.phone_number_id');

        if (empty($token) || empty($phoneNumberId)) {
            Log::info('WhatsAppService dry-run (credentials absents, message non envoyé)', [
                'to' => $toPhoneE164,
                'message' => $message,
            ]);

            return true;
        }

        $version = config('services.whatsapp.api_version', 'v21.0');

        $response = Http::withToken($token)->post(
            "https://graph.facebook.com/{$version}/{$phoneNumberId}/messages",
            [
                'messaging_product' => 'whatsapp',
                'to' => $toPhoneE164,
                'type' => 'text',
                'text' => ['body' => $message],
            ]
        );

        if ($response->failed()) {
            Log::error('WhatsAppService: échec de l\'envoi via Meta Cloud API', [
                'to' => $toPhoneE164,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        }

        return true;
    }
}
