<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('patient.titre_html', ['nom' => $patient->Nom]) }}</title>
    <link rel="stylesheet" href="/css/app.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .status-en-attente { @apply bg-yellow-100 text-yellow-800; }
        .status-confirme { @apply bg-green-100 text-green-800; }
        .status-annule { @apply bg-red-100 text-red-800; }
        .status-termine { @apply bg-blue-100 text-blue-800; }
        .current-patient { @apply bg-blue-500 text-white; }
        .other-patient { @apply bg-gray-200 text-gray-700; }

        /* RTL specific styles */
        .rtl-text { direction: rtl; text-align: right; }
        .icon-left { margin-left: 0.5rem; margin-right: 0; }
        .icon-right { margin-right: 0.5rem; margin-left: 0; }
    </style>
    @livewireStyles
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4 py-6 max-w-4xl">
                 <!-- En-tête du cabinet -->
         <div class="bg-white rounded-lg shadow-md p-6 mb-6">
             @include('partials.recu-header')
         </div>

                  <!-- Informations principales -->
         <div class="bg-white rounded-lg shadow-md p-6 mb-6">
             <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                 <!-- Informations du patient -->
                 <div>
                     <h2 class="text-xl font-bold text-gray-800 mb-4">
                        <i class="fas fa-user text-blue-600 icon-left"></i>
                        {{ __('patient.infos_patient') }}
                     </h2>
                     <div class="space-y-3">
                         <div>
                            <span class="text-sm font-medium text-gray-600">{{ __('patient.nom') }}</span>
                             <span class="text-lg font-semibold text-gray-800">{{ $patient->Nom }}</span>
                         </div>
                         @if($prochainRdv)
                             <div>
                                <span class="text-sm font-medium text-gray-600">{{ __('patient.medecin') }}</span>
                                <span class="text-lg font-semibold text-gray-800">د. {{ $prochainRdv->medecin->Nom ?? '' }} {{ $prochainRdv->medecin->Prenom ?? '' }}</span>
                             </div>
                             <div>
                                <span class="text-sm font-medium text-gray-600">{{ __('patient.date') }}</span>
                                 <span class="text-lg font-semibold text-gray-800">{{ \Carbon\Carbon::parse($prochainRdv->dtPrevuRDV)->format('d/m/Y') }}</span>
                             </div>
                         @endif
                     </div>
                 </div>

                                                 <!-- Statut actuel -->
                 <div>
                     <h2 class="text-xl font-bold text-gray-800 mb-4">
                        <i class="fas fa-clock text-green-600 icon-left"></i>
                        {{ __('patient.statut_actuel') }}
                     </h2>
                    @if($prochainRdv)
                        @if($estAujourdhui)
                            <!-- Rendez-vous aujourd'hui : file d'attente -->
                         <div class="bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg p-4 text-white">
                             <div class="text-center">
                                    <div class="text-3xl font-bold mb-2">{{ $prochainRdv->OrdreRDV ?? $positionPatient }}</div>
                                    <div class="text-sm opacity-90">{{ __('patient.numero_rdv') }}</div>

                                    @if($prochainRdv->rdvConfirmer == 'En cours')
                                        <!-- Patient avec le médecin -->
                                        <div class="bg-green-500 bg-opacity-30 rounded-lg p-3 mt-3">
                                            <div class="text-lg font-bold text-green-200">
                                                <i class="fas fa-user-md icon-left"></i>
                                                {{ __('patient.avec_medecin') }}
                                            </div>
                                            <div class="text-sm opacity-90">{{ __('patient.en_traitement') }}</div>
                                        </div>
                                    @elseif($patientsAvantMoi > 0)
                                        <div class="bg-white bg-opacity-20 rounded-lg p-3 mt-3">
                                            <div class="text-2xl font-bold text-yellow-200">{{ $patientsAvantMoi }}</div>
                                            <div class="text-sm opacity-90">
                                                <i class="fas fa-users icon-left"></i>
                                                {{ __('patient.patients_avant_moi') }}
                                            </div>
                                        </div>
                                    @else
                                        <div class="bg-green-500 bg-opacity-30 rounded-lg p-3 mt-3">
                                            <div class="text-lg font-bold text-green-200">
                                                <i class="fas fa-star icon-left"></i>
                                                {{ __('patient.votre_tour') }}
                                            </div>
                                            <div class="text-sm opacity-90">{{ __('patient.vous_pouvez_venir') }}</div>
                                     </div>
                                 @endif

                                    @if($tempsAttenteEstime > 0)
                                        <div class="text-sm opacity-90 mt-2">
                                            <i class="fas fa-hourglass-half icon-left"></i>
                                            {{ __('patient.temps_attente_estime', ['minutes' => $tempsAttenteEstime]) }}
                                        </div>
                                    @else
                                     <div class="text-sm opacity-90 mt-2">
                                            <i class="fas fa-clock icon-left"></i>
                                            {{ __('patient.pas_attente') }}
                                     </div>
                                 @endif
                             </div>
                         </div>
                                                 @elseif($estFutur)
                             <!-- Rendez-vous futur -->
                             <div class="bg-orange-100 border border-orange-300 rounded-lg p-4">
                                 <div class="text-center">
                                     <i class="fas fa-calendar-alt text-orange-600 text-3xl mb-3"></i>
                                     <div class="text-orange-800 font-bold text-lg mb-2">{{ __('patient.rdv_futur_titre') }}</div>
                                     <div class="text-orange-700 text-sm mb-3">
                                         {!! __('patient.rdv_futur_date', ['date' => '<strong>' . \Carbon\Carbon::parse($prochainRdv->dtPrevuRDV)->format('d/m/Y') . '</strong>']) !!}
                                     </div>
                                     <div class="text-orange-600 text-xs">
                                         <i class="fas fa-info-circle icon-left"></i>
                                         {{ __('patient.rdv_futur_info') }}
                                     </div>
                                 </div>
                             </div>
                         @elseif($estPasse)
                             <!-- Rendez-vous passé -->
                             <div class="bg-red-100 border border-red-300 rounded-lg p-4">
                                 <div class="text-center">
                                     <i class="fas fa-calendar-times text-red-600 text-3xl mb-3"></i>
                                     <div class="text-red-800 font-bold text-lg mb-2">{{ __('patient.rdv_passe_titre') }}</div>
                                     <div class="text-red-700 text-sm mb-3">
                                         {!! __('patient.rdv_passe_date', ['date' => '<strong>' . \Carbon\Carbon::parse($prochainRdv->dtPrevuRDV)->format('d/m/Y') . '</strong>']) !!}
                                     </div>
                                     <div class="text-red-600 text-xs">
                                         <i class="fas fa-exclamation-triangle icon-left"></i>
                                         {{ __('patient.rdv_passe_info') }}
                                     </div>
                                 </div>
                             </div>
                         @endif
                     @else
                         <div class="bg-yellow-100 border border-yellow-300 rounded-lg p-4">
                             <div class="text-center">
                                 <i class="fas fa-info-circle text-yellow-600 text-2xl mb-2"></i>
                                <div class="text-yellow-800 font-medium">{{ __('patient.aucun_rdv') }}</div>
                             </div>
                         </div>
                     @endif
                 </div>

                                 <!-- Patient actuel -->
                @if($prochainRdv && $patientEnCours && $estAujourdhui)
                     <div>
                         <h2 class="text-xl font-bold text-gray-800 mb-4">
                            <i class="fas fa-user-md text-green-600 icon-left"></i>
                            {{ __('patient.patient_actuel') }}
                         </h2>
                         <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-lg p-4 text-white">
                             <div class="text-center">
                                 <div class="text-3xl font-bold mb-2">
                                    <i class="fas fa-user-md icon-left"></i>
                                    {{ __('patient.numero', ['numero' => $patientEnCours->OrdreRDV ?? $positionPatientEnCours ?? __('patient.non_disponible')]) }}
                                 </div>
                                <div class="text-sm opacity-90">{{ __('patient.mep_avec_medecin') }}</div>
                                <div class="text-xs opacity-75 mt-1">{{ __('patient.en_traitement') }}</div>
                             </div>
                         </div>
                     </div>
                 @endif
             </div>
         </div>

        <!-- Patient en cours de traitement -->
        @if($prochainRdv && $patientEnCours && $estAujourdhui)
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-green-800">
                            <i class="fas fa-user-md icon-left"></i>
                            {{ __('patient.patient_en_traitement_titre') }}
                        </h3>
                        <p class="text-green-700">{{ __('patient.label_numero') }} <span class="font-bold">{{ $patientEnCours->OrdreRDV ?? $positionPatientEnCours ?? __('patient.non_disponible') }}</span></p>
                    </div>
                    <div class="text-left">
                                                 <div class="text-2xl font-bold text-green-600">
                             <i class="fas fa-user-md icon-left"></i>
                             {{ __('patient.mep_avec_medecin') }}
                         </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Patient actuel avec le médecin -->
        @if($prochainRdv && $prochainRdv->rdvConfirmer == 'En cours' && $estAujourdhui)
            <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-lg p-6 mb-6 text-white text-center">
                <div class="text-4xl mb-4">
                    <i class="fas fa-user-md"></i>
                </div>
                <div class="text-2xl font-bold mb-2">{{ __('patient.avec_medecin') }}</div>
                <div class="text-lg opacity-90">{{ __('patient.en_traitement_veuillez_patienter') }}</div>
                <div class="text-sm opacity-75 mt-2">
                    <i class="fas fa-clock icon-left"></i>
                    {{ __('patient.numero_rdv') }} {{ $prochainRdv->OrdreRDV }}
                </div>
            </div>
        @endif

        <!-- Programme du médecin -->
        <livewire:patient-queue-status :token="$token" wire:key="patient-queue-{{ $patient->ID }}" />



        <!-- Pied de page -->
        <div class="mt-8 text-center text-sm text-gray-500">
            <p>{{ __('patient.footer_maj') }}</p>
            <p class="mt-2">
                <i class="fas fa-shield-alt icon-left"></i>
                {{ __('patient.footer_confidentiel') }}
            </p>
        </div>
    </div>

    @livewireScripts
</body>
</html>
