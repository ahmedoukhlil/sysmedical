@extends('layouts.app')

@section('content')
<div class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="modal-title-users-index">
    <div class="modal-box max-w-5xl w-full" tabindex="-1">
        <div class="modal-header">
            <h2 id="modal-title-users-index"><i class="fas fa-users-cog mr-2"></i>Gestion des utilisateurs</h2>
            <a href="{{ route('accueil.patient') }}" class="modal-close" aria-label="Fermer"><i class="fas fa-times"></i></a>
        </div>
        <div class="modal-body">
            @livewire('user-manager')
        </div>
    </div>
</div>
@endsection
