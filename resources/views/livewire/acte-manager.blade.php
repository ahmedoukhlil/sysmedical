<div class="p-4 md:p-6">

    {{-- Formulaire ajout --}}
    <div class="mb-6 bg-primary-light border-l-4 border-primary rounded-lg p-4">
        <h3 class="text-sm font-semibold text-primary uppercase tracking-wide mb-4">
            <i class="fas fa-plus mr-1"></i> Ajouter un acte
        </h3>
        <form wire:submit.prevent="save" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3 items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nom de l'acte *</label>
                <input type="text" wire:model.defer="acteNom" class="form-input" placeholder="Nom de l'acte">
                @error('acteNom') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Prix de référence *</label>
                <input type="number" step="0.01" wire:model.defer="montant" class="form-input" placeholder="0.00">
                @error('montant') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Assureur *</label>
                <select wire:model.defer="assureurId" class="form-select">
                    <option value="">Sélectionner un assureur</option>
                    @foreach($assureurs as $assureur)
                    <option value="{{ $assureur->IDAssureur ?? $assureur->ID }}">{{ $assureur->LibAssurance }}</option>
                    @endforeach
                </select>
                @error('assureurId') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
            <button type="submit" wire:loading.attr="disabled" wire:target="save" class="btn-primary disabled:opacity-60">
                <span wire:loading.remove wire:target="save"><i class="fas fa-save"></i> Enregistrer</span>
                <span wire:loading wire:target="save"><i class="fas fa-spinner fa-spin"></i> Enregistrement...</span>
            </button>
        </form>
        <div class="mt-3">
            <label class="block text-sm font-medium text-gray-700 mb-1">Nom arabe (optionnel)</label>
            <input type="text" wire:model.defer="acteArab" class="form-input md:w-1/2" placeholder="Nom arabe de l'acte">
        </div>
    </div>

    {{-- Filtres --}}
    <div class="mb-4 flex flex-col md:flex-row gap-3">
        <div class="flex items-center gap-2">
            <label class="text-sm font-medium text-gray-700 whitespace-nowrap">Assureur :</label>
            <select wire:model="selectedAssureur" class="form-select">
                <option value="">Tous</option>
                @foreach($assureurs as $assureur)
                <option value="{{ $assureur->IDAssureur ?? $assureur->ID }}">{{ $assureur->LibAssurance }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex-1 relative">
            <input type="text" wire:model="search" class="form-input" placeholder="Rechercher un acte...">
            <span wire:loading wire:target="search" class="absolute right-3 top-1/2 -translate-y-1/2"><i class="fas fa-spinner fa-spin text-gray-400"></i></span>
        </div>
    </div>

    {{-- Tableau --}}
    <div class="overflow-x-auto max-h-[50vh] overflow-y-auto rounded-lg border border-gray-200">
        <table>
            <thead>
                <tr>
                    <th>Acte</th>
                    <th>Prix de référence</th>
                    <th>Assureur</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($actes as $acte)
                <tr>
                    <td>
                        {{ $acte->Acte }}
                        @if($acte->ActeArab)
                        <div class="text-xs text-gray-500">{{ $acte->ActeArab }}</div>
                        @endif
                    </td>
                    <td>{{ number_format($acte->PrixRef, 2) }} MRU</td>
                    <td>{{ $acte->assureur->LibAssurance ?? '-' }}</td>
                    <td>
                        <button wire:click="openModal({{ $acte->ID }})" class="text-primary hover:text-primary-dark text-sm font-medium mr-3">
                            <i class="fas fa-edit"></i> Modifier
                        </button>
                        <button wire:click="confirmDelete({{ $acte->ID }})" class="text-red-600 hover:text-red-800 text-sm font-medium">
                            <i class="fas fa-trash"></i> Supprimer
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="text-center text-gray-400 py-8">
                        <i class="fas fa-list-alt text-2xl mb-2 block"></i> Aucun acte trouvé
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $actes->links() }}</div>

    {{-- Modal édition --}}
    @if($showModal)
    <div class="modal-overlay" style="z-index:60;" role="dialog" aria-modal="true" aria-labelledby="modal-title-acte-edit">
        <div class="modal-box max-w-lg w-full" tabindex="-1">
            <div class="modal-header">
                <h2 id="modal-title-acte-edit"><i class="fas fa-edit mr-2"></i>Modifier un acte</h2>
                <button wire:click="closeModal" class="modal-close" aria-label="Fermer"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <form wire:submit.prevent="save" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nom de l'acte *</label>
                        <input type="text" wire:model.defer="acteNom" class="form-input">
                        @error('acteNom') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Prix de référence *</label>
                        <input type="number" step="0.01" wire:model.defer="montant" class="form-input">
                        @error('montant') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Assureur *</label>
                        <select wire:model.defer="assureurId" class="form-select">
                            <option value="">Sélectionner un assureur</option>
                            @foreach($assureurs as $assureur)
                            <option value="{{ $assureur->IDAssureur ?? $assureur->ID }}">{{ $assureur->LibAssurance }}</option>
                            @endforeach
                        </select>
                        @error('assureurId') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nom arabe</label>
                        <input type="text" wire:model.defer="acteArab" class="form-input">
                    </div>
                    <div class="modal-footer px-0 pb-0">
                        <button type="button" wire:click="closeModal" class="btn-secondary">Annuler</button>
                        <button type="submit" wire:loading.attr="disabled" wire:target="save" class="btn-primary disabled:opacity-60">
                            <span wire:loading.remove wire:target="save">Enregistrer</span>
                            <span wire:loading wire:target="save"><i class="fas fa-spinner fa-spin mr-1"></i> Enregistrement...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    {{-- Modal suppression --}}
    @if($showDeleteModal)
    <div class="modal-overlay" style="z-index:60;" role="dialog" aria-modal="true" aria-labelledby="modal-title-acte-delete">
        <div class="modal-box max-w-md w-full" tabindex="-1">
            <div class="modal-header">
                <h2 id="modal-title-acte-delete"><i class="fas fa-exclamation-triangle mr-2"></i>Confirmer la suppression</h2>
                <button wire:click="$set('showDeleteModal', false)" class="modal-close" aria-label="Fermer"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <p class="text-gray-600">Êtes-vous sûr de vouloir supprimer cet acte ? Cette action est irréversible.</p>
                <div class="modal-footer px-0 pb-0 mt-6">
                    <button wire:click="$set('showDeleteModal', false)" class="btn-secondary">Annuler</button>
                    <button wire:click="deleteActe" wire:loading.attr="disabled" wire:target="deleteActe" class="btn-danger disabled:opacity-60">
                        <span wire:loading.remove wire:target="deleteActe">Supprimer</span>
                        <span wire:loading wire:target="deleteActe"><i class="fas fa-spinner fa-spin mr-1"></i> Suppression...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

</div>
