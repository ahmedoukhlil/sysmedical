<div wire:poll.30s="refresh">
    @if($prochainRdv && $rendezVousMedecinJournee->count() > 0 && $estAujourdhui)
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h2 class="text-lg font-semibold text-gray-800">
                    <i class="fas fa-calendar-day icon-left"></i>
                    {{ __('patient.programme_medecin', ['medecin' => trim(($prochainRdv->medecin->Nom ?? '') . ' ' . ($prochainRdv->medecin->Prenom ?? ''))]) }}
                    <span class="text-sm font-normal text-gray-600">
                        - {{ \Carbon\Carbon::parse($prochainRdv->dtPrevuRDV)->format('d/m/Y') }}
                    </span>
                </h2>
            </div>

            <div class="overflow-x-auto" aria-live="polite" aria-atomic="true">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <i class="fas fa-sort-numeric-up icon-left"></i>{{ __('patient.numero_rdv_col') }}
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <i class="fas fa-clock icon-left"></i>{{ __('patient.heure_col') }}
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <i class="fas fa-info-circle icon-left"></i>{{ __('patient.statut_col') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($rendezVousMedecinJournee as $index => $rdv)
                            @php
                                $isCurrentPatient = $rdv->fkidPatient == $patient->ID;
                                $isEnCours = $rdv->rdvConfirmer == 'En cours';
                                $rowClass = $isCurrentPatient ? 'bg-blue-50 border-l-4 border-blue-500' : ($isEnCours ? 'bg-green-50 border-l-4 border-green-500' : 'hover:bg-gray-50');
                                $statutCle = \App\Support\RdvStatus::normalize($rdv->rdvConfirmer);
                            @endphp
                            <tr class="{{ $rowClass }}">
                                <td class="px-4 py-3 whitespace-nowrap text-right">
                                    <div class="flex items-center justify-end">
                                        <span class="inline-flex items-center justify-center w-8 h-8 {{ $isCurrentPatient ? 'current-patient' : ($isEnCours ? 'bg-green-500 text-white' : 'other-patient') }} text-sm font-bold rounded-full ml-3">
                                            {{ $rdv->OrdreRDV ?? ($index + 1) }}
                                        </span>
                                        @if($isCurrentPatient)
                                            <span class="text-sm text-blue-600 font-medium">{{ $rdv->patient->Nom ?? __('patient.vous') }}</span>
                                        @elseif($isEnCours)
                                            <span class="text-sm text-green-600 font-medium">{{ __('patient.mep_avec_medecin') }}</span>
                                        @else
                                            <span class="text-sm text-gray-500">{{ __('patient.patient_masque') }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-right">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ \Carbon\Carbon::parse($rdv->HeureRdv)->format('H:i') }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-right">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ \App\Support\RdvStatus::badgeClasses($statutCle) }}">
                                        <i class="{{ \App\Support\RdvStatus::icon($statutCle) }} icon-left"></i>
                                        {{ __('rdv.statuts.' . $statutCle) }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @elseif($prochainRdv && !$estAujourdhui)
        {{-- Rendez-vous futur : programme du médecin non affiché --}}
    @else
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-800">
                    <i class="fas fa-calendar-day icon-left"></i>
                    {{ __('patient.programme_medecin_generique') }}
                </h2>
            </div>
            <div class="text-center py-12">
                <i class="fas fa-calendar-times text-4xl text-gray-400 mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">{{ __('patient.aucun_programme_titre') }}</h3>
                <p class="text-gray-500">{{ __('patient.aucun_programme_texte') }}</p>
            </div>
        </div>
    @endif
</div>
