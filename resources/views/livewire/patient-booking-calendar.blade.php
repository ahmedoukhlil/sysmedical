<div class="max-w-lg mx-auto p-6">
    @if($confirme)
        <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-center text-green-800">
            Votre rendez-vous a été enregistré. Vous recevrez une confirmation par WhatsApp.
        </div>
    @else
        @if($errorMessage)
            <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg p-3 mb-4 text-sm">
                {{ $errorMessage }}
            </div>
        @endif

        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Médecin</label>
                <select wire:model="medecinId" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                    <option value="">Choisir un médecin</option>
                    @foreach($medecins as $medecin)
                        <option value="{{ $medecin['idMedecin'] }}">{{ $medecin['Nom'] }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Date</label>
                <input type="date" wire:model="date" min="{{ now()->format('Y-m-d') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2">
            </div>

            @if(count($creneaux) > 0)
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Créneau disponible</label>
                    <div class="grid grid-cols-4 gap-2">
                        @foreach($creneaux as $creneau)
                            <button type="button" wire:click="$set('heureChoisie', '{{ $creneau }}')"
                                class="px-2 py-1 rounded-lg border text-sm {{ $heureChoisie === $creneau ? 'bg-primary text-white border-primary' : 'border-gray-300' }}">
                                {{ $creneau }}
                            </button>
                        @endforeach
                    </div>
                </div>
            @elseif($medecinId)
                <p class="text-sm text-gray-500">Aucun créneau disponible pour cette date.</p>
            @endif

            <button type="button" wire:click="confirmerRdv" wire:loading.attr="disabled"
                class="w-full bg-primary text-white rounded-lg py-2 disabled:opacity-50">
                Confirmer le rendez-vous
            </button>
        </div>
    @endif
</div>
