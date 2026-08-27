<div class="max-w-lg mx-auto p-6 bg-white rounded shadow">
    <h2 class="text-2xl font-bold text-gray-800 mb-6 text-left">Créer un assureur</h2>

    <form wire:submit.prevent="save" class="space-y-6">
        <div>
            <label class="block text-base font-bold text-gray-700 mb-1">Nom de l'assureur <span class="text-red-500">*</span></label>
            <input type="text" wire:model.defer="LibAssurance" class="mt-1 block w-full rounded-md border-gray-400 shadow focus:border-primary focus:ring-primary h-12 text-lg bg-gray-50" required>
            @error('LibAssurance') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
        </div>
        <div>
            <label class="block text-base font-bold text-gray-700 mb-1">Taux de prise en charge (%) <span class="text-red-500">*</span></label>
            <input type="number" wire:model.defer="TauxdePEC" min="0" max="100" step="0.01" class="mt-1 block w-full rounded-md border-gray-400 shadow focus:border-primary focus:ring-primary h-12 text-lg bg-gray-50" required>
            @error('TauxdePEC') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
        </div>
        <div>
            <label class="block text-base font-bold text-gray-700 mb-1">Date de convention <span class="text-red-500">*</span></label>
            <input type="date" wire:model.defer="DtConvention" class="mt-1 block w-full rounded-md border-gray-400 shadow focus:border-primary focus:ring-primary h-12 text-lg bg-gray-50" required>
            @error('DtConvention') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
        </div>
        <div class="flex justify-end">
            <button type="submit" wire:loading.attr="disabled" wire:target="save" class="px-6 py-3 bg-primary text-white rounded hover:bg-primary-dark text-lg font-bold disabled:opacity-60">
                <span wire:loading.remove wire:target="save">Enregistrer</span>
                <span wire:loading wire:target="save"><i class="fas fa-spinner fa-spin mr-1"></i> Enregistrement...</span>
            </button>
        </div>
    </form>
</div> 