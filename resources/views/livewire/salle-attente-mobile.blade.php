<div class="p-3" wire:poll.15s>

    <div class="flex flex-wrap gap-2 mb-4 items-center">
        <input type="date" wire:model="date" wire:change="$refresh"
            class="border border-gray-300 rounded-lg px-3 py-2 text-sm flex-1">
        @if(count($medecins) > 1)
            <select wire:model="medecinFiltre" wire:change="$refresh"
                class="border border-gray-300 rounded-lg px-3 py-2 text-sm flex-1">
                <option value="">Tous les médecins</option>
                @foreach($medecins as $med)
                    <option value="{{ $med->idMedecin }}">Dr. {{ $med->Nom }}</option>
                @endforeach
            </select>
        @endif
    </div>

    @if($rdvParMedecin->isEmpty())
        <div class="text-center py-16 text-gray-400">
            <i class="fas fa-couch text-4xl mb-3 opacity-40"></i>
            <p class="font-medium">Salle d'attente vide</p>
        </div>
    @else
        @foreach($rdvParMedecin as $medecinId => $rdvs)
            @php $medecin = $rdvs->first()->medecin; @endphp
            <div class="mb-5">
                <div class="flex items-center gap-2 mb-2 pb-2 border-b-2 border-primary/20">
                    <h3 class="font-semibold text-gray-800 text-sm">Dr. {{ $medecin->Nom ?? 'Médecin inconnu' }}</h3>
                    <span class="ml-auto text-xs text-gray-500 bg-gray-100 px-2 py-0.5 rounded-full">{{ $rdvs->count() }}</span>
                </div>

                <div class="space-y-2">
                    @foreach($rdvs as $rdv)
                        @php
                            $patient = $rdv->patient;
                            $nomPatient = $patient ? ($patient->NomContact ?? ($patient->Prenom ?? 'Patient inconnu')) : 'Patient inconnu';
                            $enCours = strtolower($rdv->rdvConfirmer ?? '') === 'en cours';
                        @endphp
                        <div class="p-3 rounded-xl shadow-sm border {{ $enCours ? 'bg-blue-50 border-blue-300 ring-2 ring-blue-200' : 'bg-white border-gray-100' }}">
                            <div class="flex items-center gap-3">
                                <div class="text-center min-w-[46px]">
                                    <span class="text-sm font-bold {{ $enCours ? 'text-blue-700' : 'text-primary' }}">
                                        {{ $rdv->HeureRdv ? \Carbon\Carbon::parse($rdv->HeureRdv)->format('H:i') : '--:--' }}
                                    </span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-semibold text-sm truncate {{ $enCours ? 'text-blue-800' : 'text-gray-800' }}">{{ $nomPatient }}</p>
                                    @if($rdv->ActePrevu)
                                        <p class="text-xs text-gray-500 truncate">{{ $rdv->ActePrevu }}</p>
                                    @endif
                                </div>
                                <x-status-badge :status="$rdv->rdvConfirmer" />
                            </div>
                            @if($patient)
                                <div class="flex gap-2 mt-2">
                                    @if(!$enCours)
                                        <button wire:click="demarrerRdv({{ $rdv->IDRdv }})" wire:loading.attr="disabled" wire:target="demarrerRdv({{ $rdv->IDRdv }})"
                                            class="flex-1 py-2 rounded-lg bg-primary text-white text-xs font-semibold disabled:opacity-60">
                                            <span wire:loading.remove wire:target="demarrerRdv({{ $rdv->IDRdv }})">Démarrer</span>
                                            <span wire:loading wire:target="demarrerRdv({{ $rdv->IDRdv }})"><i class="fas fa-spinner fa-spin"></i></span>
                                        </button>
                                    @else
                                        <button wire:click="terminerRdv({{ $rdv->IDRdv }})" wire:loading.attr="disabled" wire:target="terminerRdv({{ $rdv->IDRdv }})"
                                            class="flex-1 py-2 rounded-lg bg-green-500 text-white text-xs font-semibold disabled:opacity-60">
                                            <span wire:loading.remove wire:target="terminerRdv({{ $rdv->IDRdv }})">Terminer</span>
                                            <span wire:loading wire:target="terminerRdv({{ $rdv->IDRdv }})"><i class="fas fa-spinner fa-spin"></i></span>
                                        </button>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    @endif
</div>
