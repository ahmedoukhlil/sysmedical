<?php

namespace App\Support;

use App\Models\Rendezvou;
use Carbon\Carbon;

class RdvMessageFormatter
{
    /**
     * Message bilingue (arabe puis français) de rappel/confirmation de RDV,
     * avec le lien de suivi de la file d'attente. Factorise le template
     * précédemment dupliqué dans RdvReminders::generateReminderMessage().
     */
    public static function format(Rendezvou $rdv, string $titreAr, string $titreFr, bool $demanderConfirmation = false): string
    {
        $patientName = $rdv->patient->Nom;
        $rdvDate = Carbon::parse($rdv->dtPrevuRDV)->format('d/m/Y');
        $rdvTime = Carbon::parse($rdv->HeureRdv)->format('H:i');
        $medecinName = $rdv->medecin ? 'Dr. ' . $rdv->medecin->Nom : 'le médecin';
        $acte = $rdv->ActePrevu ?: 'Consultation';

        $token = \App\Http\Controllers\PatientInterfaceController::generateToken(
            $rdv->patient->ID,
            $rdv->dtPrevuRDV,
            $rdv->fkidMedecin
        );
        $queueLink = url("/patient/rendez-vous/{$token}");

        $message = "🔔 *{$titreAr}* 🔔\n\n";
        $message .= "مرحباً *{$patientName}*،\n\n";
        $message .= "📅 *التاريخ :* {$rdvDate}\n";
        $message .= "🕐 *الوقت :* {$rdvTime}\n";
        $message .= "👨‍⚕️ *الطبيب :* {$medecinName}\n";
        $message .= "🦷 *العملية :* {$acte}\n\n";
        if ($demanderConfirmation) {
            $message .= "⚠️ *يرجى تأكيد حضوركم بالرد على هذه الرسالة.*\n\n";
        }
        $message .= "*رابط متابعة طابور الانتظار:*\n";
        $message .= "{$queueLink}\n\n";
        $message .= "───────────────────\n\n";
        $message .= "🔔 *{$titreFr}* 🔔\n\n";
        $message .= "Bonjour *{$patientName}*,\n\n";
        $message .= "📅 *Date :* {$rdvDate}\n";
        $message .= "🕐 *Heure :* {$rdvTime}\n";
        $message .= "👨‍⚕️ *Médecin :* {$medecinName}\n";
        $message .= "🦷 *Acte :* {$acte}\n\n";
        if ($demanderConfirmation) {
            $message .= "⚠️ *Veuillez confirmer votre présence en répondant à ce message.*\n\n";
        }
        $message .= "*Lien de suivi de la file d'attente:*\n";
        $message .= "{$queueLink}\n\n";
        $message .= "شكراً / Merci";

        return $message;
    }

    public static function formatReminder(Rendezvou $rdv): string
    {
        return self::format($rdv, 'تذكير بالموعد', 'RAPPEL RENDEZ-VOUS', true);
    }

    public static function formatConfirmation(Rendezvou $rdv): string
    {
        return self::format($rdv, 'تأكيد الموعد', 'CONFIRMATION DE RENDEZ-VOUS', false);
    }
}
