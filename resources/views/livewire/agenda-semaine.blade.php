<div class="p-4 space-y-4" wire:poll.15s>
    <div class="flex items-center justify-between bg-white rounded-xl border border-gray-200 p-3">
        <button wire:click="semainePrecedente" class="text-gray-500 hover:text-primary p-2">
            <i class="fas fa-chevron-left"></i>
        </button>
        <div class="text-center">
            <div class="text-sm font-semibold text-gray-700">
                {{ \Carbon\Carbon::parse($semaineDebut)->format('d/m') }}
                -
                {{ \Carbon\Carbon::parse($semaineDebut)->addDays(6)->format('d/m') }}
            </div>
            <button wire:click="semaineActuelle" class="text-xs text-primary underline">Aujourd'hui</button>
        </div>
        <button wire:click="semaineSuivante" class="text-gray-500 hover:text-primary p-2">
            <i class="fas fa-chevron-right"></i>
        </button>
    </div>

    @if(count($medecins) > 1)
        <select wire:model="medecinFiltre" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            <option value="">Tous les médecins</option>
            @foreach($medecins as $medecin)
                <option value="{{ $medecin->idMedecin }}">{{ $medecin->Nom }}</option>
            @endforeach
        </select>
    @endif

    @foreach($jours as $jour)
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-4 py-2 bg-gray-50 border-b border-gray-100 sticky top-0">
                <span class="text-sm font-semibold text-gray-700">{{ ucfirst($jour['date']->translatedFormat('l d/m')) }}</span>
            </div>

            @if($jour['rdvs']->isEmpty())
                <div class="px-4 py-3 text-sm text-gray-400">Aucun rendez-vous</div>
            @else
                <div class="divide-y divide-gray-100">
                    @foreach($jour['rdvs'] as $rdv)
                        <div class="px-4 py-3 flex items-center justify-between gap-2">
                            <div>
                                <div class="text-sm font-medium text-gray-900">
                                    {{ \Carbon\Carbon::parse($rdv->HeureRdv)->format('H:i') }}
                                    — {{ $rdv->patient->Nom ?? 'Patient' }}
                                </div>
                                <div class="text-xs text-gray-500">{{ $rdv->medecin->Nom ?? '' }}</div>
                            </div>
                            <div class="flex items-center gap-2">
                                <x-status-badge :status="$rdv->rdvConfirmer" />
                                @if(!in_array($rdv->rdvConfirmer, ['En cours', 'Terminé', 'terminé']))
                                    <button wire:click="demarrerRdv({{ $rdv->IDRdv }})" wire:loading.attr="disabled" wire:target="demarrerRdv({{ $rdv->IDRdv }})" class="text-xs px-2 py-1 rounded bg-green-100 text-green-700 disabled:opacity-60">
                                        <span wire:loading.remove wire:target="demarrerRdv({{ $rdv->IDRdv }})">Démarrer</span>
                                        <span wire:loading wire:target="demarrerRdv({{ $rdv->IDRdv }})"><i class="fas fa-spinner fa-spin"></i></span>
                                    </button>
                                @elseif($rdv->rdvConfirmer === 'En cours')
                                    <button wire:click="terminerRdv({{ $rdv->IDRdv }})" wire:loading.attr="disabled" wire:target="terminerRdv({{ $rdv->IDRdv }})" class="text-xs px-2 py-1 rounded bg-gray-100 text-gray-700 disabled:opacity-60">
                                        <span wire:loading.remove wire:target="terminerRdv({{ $rdv->IDRdv }})">Terminer</span>
                                        <span wire:loading wire:target="terminerRdv({{ $rdv->IDRdv }})"><i class="fas fa-spinner fa-spin"></i></span>
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endforeach
</div>
