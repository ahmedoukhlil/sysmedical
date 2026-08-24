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

    <form method="POST" action="{{ route('admin.cabinets.store') }}" class="bg-white rounded-lg shadow p-6 max-w-lg space-y-4">
        @csrf

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Nom du cabinet</label>
            <input type="text" name="nom_cabinet" value="{{ old('nom_cabinet') }}" class="w-full border rounded px-3 py-2" required>
        </div>

        <hr>

        <p class="text-sm text-gray-500">Compte propriétaire initial</p>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Nom complet</label>
            <input type="text" name="owner_nom" value="{{ old('owner_nom') }}" class="w-full border rounded px-3 py-2" required>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Identifiant de connexion</label>
            <input type="text" name="owner_login" value="{{ old('owner_login') }}" class="w-full border rounded px-3 py-2" required>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Mot de passe</label>
            <input type="password" name="owner_password" class="w-full border rounded px-3 py-2" required minlength="8">
        </div>

        <button type="submit" class="bg-blue-800 text-white px-4 py-2 rounded hover:bg-blue-900">Créer le cabinet</button>
    </form>
@endsection
