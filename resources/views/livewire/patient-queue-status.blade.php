<div wire:poll.30s="refresh">
    @if($prochainRdv && $rendezVousMedecinJournee->count() > 0 && $estAujourdhui)
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h2 class="text-lg font-semibold text-gray-800">
                    <i class="fas fa-calendar-day icon-left"></i>
                    برنامج د. {{ $prochainRdv->medecin->Nom ?? '' }} {{ $prochainRdv->medecin->Prenom ?? '' }}
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
                                <i class="fas fa-sort-numeric-up icon-left"></i>رقم الموعد
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <i class="fas fa-clock icon-left"></i>الوقت
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <i class="fas fa-info-circle icon-left"></i>الحالة
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($rendezVousMedecinJournee as $index => $rdv)
                            @php
                                $isCurrentPatient = $rdv->fkidPatient == $patient->ID;
                                $isEnCours = $rdv->rdvConfirmer == 'En cours';
                                $rowClass = $isCurrentPatient ? 'bg-blue-50 border-l-4 border-blue-500' : ($isEnCours ? 'bg-green-50 border-l-4 border-green-500' : 'hover:bg-gray-50');
                            @endphp
                            <tr class="{{ $rowClass }}">
                                <td class="px-4 py-3 whitespace-nowrap text-right">
                                    <div class="flex items-center justify-end">
                                        <span class="inline-flex items-center justify-center w-8 h-8 {{ $isCurrentPatient ? 'current-patient' : ($isEnCours ? 'bg-green-500 text-white' : 'other-patient') }} text-sm font-bold rounded-full ml-3">
                                            {{ $rdv->OrdreRDV ?? ($index + 1) }}
                                        </span>
                                        @if($isCurrentPatient)
                                            <span class="text-sm text-blue-600 font-medium">{{ $rdv->patient->Nom ?? 'أنت' }}</span>
                                        @elseif($isEnCours)
                                            <span class="text-sm text-green-600 font-medium">مع الطبيب</span>
                                        @else
                                            <span class="text-sm text-gray-500">مريض مخفي</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-right">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ \Carbon\Carbon::parse($rdv->HeureRdv)->format('H:i') }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-right">
                                    @php
                                        $statusClass = 'bg-yellow-100 text-yellow-800';
                                        $statusIcon = 'fas fa-clock';

                                        switch($rdv->rdvConfirmer) {
                                            case 'En Attente':
                                            case 'En attente':
                                                $statusClass = 'bg-yellow-100 text-yellow-800';
                                                $statusIcon = 'fas fa-clock';
                                                break;
                                            case 'confirmé':
                                            case 'Confirmé':
                                                $statusClass = 'bg-blue-100 text-blue-800';
                                                $statusIcon = 'fas fa-user-check';
                                                break;
                                            case 'En cours':
                                                $statusClass = 'bg-green-100 text-green-800';
                                                $statusIcon = 'fas fa-user-md';
                                                break;
                                            case 'terminé':
                                            case 'Terminé':
                                                $statusClass = 'bg-gray-100 text-gray-800';
                                                $statusIcon = 'fas fa-check-double';
                                                break;
                                            case 'annulé':
                                            case 'Annulé':
                                                $statusClass = 'bg-red-100 text-red-800';
                                                $statusIcon = 'fas fa-times';
                                                break;
                                            case 'Consultation':
                                                $statusClass = 'bg-purple-100 text-purple-800';
                                                $statusIcon = 'fas fa-stethoscope';
                                                break;
                                            default:
                                                $statusClass = 'bg-yellow-100 text-yellow-800';
                                                $statusIcon = 'fas fa-clock';
                                                break;
                                        }
                                    @endphp

                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusClass }}">
                                        <i class="{{ $statusIcon }} icon-left"></i>
                                        @switch($rdv->rdvConfirmer)
                                            @case('En Attente')
                                            @case('En attente')
                                                في الانتظار
                                                @break
                                            @case('confirmé')
                                            @case('Confirmé')
                                                حاضر في العيادة
                                                @break
                                            @case('En cours')
                                                مع الطبيب
                                                @break
                                            @case('terminé')
                                            @case('Terminé')
                                                منتهي
                                                @break
                                            @case('annulé')
                                            @case('Annulé')
                                                ملغى
                                                @break
                                            @case('Consultation')
                                                استشارة
                                                @break
                                            @default
                                                في الانتظار
                                        @endswitch
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @elseif($prochainRdv && !$estAujourdhui)
        {{-- موعد مستقبلي - لا تظهر برنامج الطبيب --}}
    @else
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-800">
                    <i class="fas fa-calendar-day icon-left"></i>
                    برنامج الطبيب
                </h2>
            </div>
            <div class="text-center py-12">
                <i class="fas fa-calendar-times text-4xl text-gray-400 mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">لا يوجد برنامج متاح</h3>
                <p class="text-gray-500">لا توجد مواعيد مبرمجة لليوم.</p>
            </div>
        </div>
    @endif
</div>
