@extends('layouts.admin')

@section('title', 'Nouveau cabinet')

@section('content')
    <h1 class="text-2xl font-bold text-gray-800 mb-6">Nouveau cabinet</h1>

    @if ($errors->any())
        <div class="mb-4 p-3 bg-red-100 text-red-800 rounded">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.cabinets.store') }}">
        @csrf

        <x-card class="max-w-lg">
            <x-form-input label="Nom du cabinet" name="nom_cabinet" value="{{ old('nom_cabinet') }}" required />

            <hr class="my-4">

            <p class="text-sm text-gray-500 mb-4">Compte propriétaire initial</p>

            <x-form-input label="Nom complet" name="owner_nom" value="{{ old('owner_nom') }}" required />

            <div class="mt-4">
                <x-form-input label="Identifiant de connexion" name="owner_login" value="{{ old('owner_login') }}" required />
            </div>

            <div class="mt-4">
                <x-form-input label="Mot de passe" name="owner_password" type="password" required minlength="8" />
            </div>

            <div class="mt-4">
                <x-button type="submit" variant="primary">Créer le cabinet</x-button>
            </div>
        </x-card>
    </form>
@endsection
