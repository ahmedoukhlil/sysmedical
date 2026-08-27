<div class="max-w-md mx-auto p-6">
    @if($tokenInvalid)
        <div class="bg-red-100 border border-red-300 rounded-lg p-4 text-center text-red-800">
            {{ __('patient.aucun_rdv') }}
        </div>
    @else
        @if($errorMessage)
            <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg p-3 mb-4 text-sm">
                {{ $errorMessage }}
            </div>
        @endif

        @if($step === 'phone')
            <form wire:submit.prevent="requestOtp" class="space-y-4">
                <label class="block text-sm font-medium text-gray-700">Numéro de téléphone</label>
                <input type="text" wire:model.defer="telephone" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                @error('telephone') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                <button type="submit" wire:loading.attr="disabled" wire:target="requestOtp" class="w-full bg-primary text-white rounded-lg py-2 disabled:opacity-60">
                    <span wire:loading.remove wire:target="requestOtp">Recevoir un code par WhatsApp</span>
                    <span wire:loading wire:target="requestOtp"><i class="fas fa-spinner fa-spin mr-1"></i> Envoi...</span>
                </button>
            </form>
        @elseif($step === 'otp')
            <form wire:submit.prevent="verifyOtp" class="space-y-4">
                <label class="block text-sm font-medium text-gray-700">Code reçu par WhatsApp</label>
                <input type="text" wire:model.defer="code" maxlength="6" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                @error('code') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                <button type="submit" wire:loading.attr="disabled" wire:target="verifyOtp" class="w-full bg-primary text-white rounded-lg py-2 disabled:opacity-60">
                    <span wire:loading.remove wire:target="verifyOtp">Valider</span>
                    <span wire:loading wire:target="verifyOtp"><i class="fas fa-spinner fa-spin mr-1"></i> Vérification...</span>
                </button>
            </form>
        @elseif($step === 'booking')
            <livewire:patient-booking-calendar :ticket="$bookingTicket" wire:key="booking-{{ $bookingTicket }}" />
        @endif
    @endif
</div>
