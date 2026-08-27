<div class="p-3" wire:poll.15s>

    <div class="flex items-center gap-2 mb-4">
        <input type="date" wire:model="date" wire:change="$refresh"
            class="border border-gray-300 rounded-lg px-3 py-2 text-sm flex-1">
    </div>

    @if($patients->isEmpty())
        <div class="text-center py-16 text-gray-400">
            <i class="fas fa-syringe text-4xl mb-3 opacity-40"></i>
            <p class="font-medium">Salle de soins vide</p>
        </div>
    @else
        <div class="space-y-3">
            @foreach($patients as $item)
                @php
                    $patient    = $item['patient'];
                    $statut     = $item['statut'];
                    $nomPatient = $patient ? ($patient->NomContact ?? $patient->Prenom ?? 'Patient inconnu') : 'Patient inconnu';
                    $ouvert     = $patientOuvert === ($patient->ID ?? null);

                    $statutStyle = match($statut) {
                        'en_cours' => ['ring' => 'border-blue-300 ring-2 ring-blue-100', 'badge' => 'bg-blue-100 text-blue-700', 'label' => 'En cours'],
                        'termine'  => ['ring' => 'border-green-300 ring-2 ring-green-100', 'badge' => 'bg-green-100 text-green-700', 'label' => 'Terminé'],
                        default    => ['ring' => 'border-yellow-200 ring-2 ring-yellow-50', 'badge' => 'bg-yellow-100 text-yellow-700', 'label' => 'En attente'],
                    };
                @endphp

                <div class="rounded-xl border shadow-sm overflow-hidden {{ $statutStyle['ring'] }}">
                    <div class="flex items-center gap-3 p-3" wire:click="togglePatient({{ $patient->ID ?? 'null' }})">
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-sm truncate">{{ $nomPatient }}</p>
                            <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded-full {{ $statutStyle['badge'] }}">
                                {{ $statutStyle['label'] }}
                            </span>
                        </div>
                        <i class="fas fa-chevron-{{ $ouvert ? 'up' : 'down' }} text-gray-400 text-xs"></i>
                    </div>

                    @if(!$canCreate && $patient)
                        <div class="flex gap-2 px-3 pb-3">
                            @if($statut === 'en_attente')
                                <button wire:click="demarrerSoins({{ $patient->ID }})" wire:loading.attr="disabled" wire:target="demarrerSoins({{ $patient->ID }})"
                                    class="flex-1 py-2 rounded-lg bg-blue-500 text-white text-xs font-semibold disabled:opacity-60">
                                    <span wire:loading.remove wire:target="demarrerSoins({{ $patient->ID }})">Démarrer</span>
                                    <span wire:loading wire:target="demarrerSoins({{ $patient->ID }})"><i class="fas fa-spinner fa-spin"></i></span>
                                </button>
                            @elseif($statut === 'en_cours')
                                <button wire:click="terminerSoins({{ $patient->ID }})" wire:loading.attr="disabled" wire:target="terminerSoins({{ $patient->ID }})"
                                    class="flex-1 py-2 rounded-lg bg-green-500 text-white text-xs font-semibold disabled:opacity-60">
                                    <span wire:loading.remove wire:target="terminerSoins({{ $patient->ID }})">Terminer</span>
                                    <span wire:loading wire:target="terminerSoins({{ $patient->ID }})"><i class="fas fa-spinner fa-spin"></i></span>
                                </button>
                            @endif
                        </div>
                    @endif

                    @if($ouvert)
                        <div class="border-t border-primary/10 bg-white px-3 py-2">
                            @foreach($item['ordonnances'] as $ord)
                                <div class="mb-2">
                                    <p class="text-xs font-semibold text-gray-600">{{ $ord['TypeOrd'] }}</p>
                                    @foreach($ord['lignes'] as $ligne)
                                        <p class="text-sm text-gray-800">{{ $ligne['libelle'] }}</p>
                                        @if($ligne['posologie'])
                                            <p class="text-xs text-gray-500">{{ $ligne['posologie'] }}</p>
                                        @endif
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
