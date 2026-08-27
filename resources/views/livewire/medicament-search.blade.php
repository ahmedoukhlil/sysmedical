<div class="relative">
    <input type="text" wire:model="search" class="w-full border rounded px-3 py-2" placeholder="Rechercher un {{ $fkidtype == 1 ? 'médicament' : ($fkidtype == 2 ? 'analyse' : 'radio') }}...">
    <span wire:loading wire:target="search" class="absolute right-3 top-2.5"><i class="fas fa-spinner fa-spin text-gray-400"></i></span>
    @if($showDropdown && strlen($search) > 0)
        <ul class="absolute z-10 bg-white border w-full mt-1 rounded shadow max-h-48 overflow-auto">
            @forelse($medicaments as $medicament)
                <li wire:click="selectMedicament({{ $medicament->IDMedic }})" class="px-3 py-2 hover:bg-blue-100 cursor-pointer">{{ $medicament->LibelleMedic }}</li>
            @empty
                <li class="px-3 py-2 text-gray-400">Aucun {{ $fkidtype == 1 ? 'médicament' : ($fkidtype == 2 ? 'analyse' : 'radio') }} trouvé</li>
            @endforelse
        </ul>
    @endif
</div>

