@extends('layouts.admin')

@section('title', 'Cabinets')

@section('content')
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Cabinets</h1>
        <a href="{{ route('admin.cabinets.create') }}" class="bg-blue-800 text-white px-4 py-2 rounded hover:bg-blue-900">
            Nouveau cabinet
        </a>
    </div>

    <div class="bg-white rounded-lg shadow overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-100 text-left text-gray-600">
                <tr>
                    <th class="px-4 py-3">Nom</th>
                    <th class="px-4 py-3">Statut</th>
                    <th class="px-4 py-3">Utilisateurs</th>
                    <th class="px-4 py-3">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($cabinets as $cabinet)
                    <tr>
                        <td class="px-4 py-3">{{ $cabinet->NomCabFr ?? '—' }}</td>
                        <td class="px-4 py-3">
                            @if ($cabinet->statut === 'actif')
                                <span class="px-2 py-1 rounded bg-green-100 text-green-800 text-xs font-semibold">Actif</span>
                            @else
                                <span class="px-2 py-1 rounded bg-red-100 text-red-800 text-xs font-semibold">Suspendu</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $cabinet->users->count() }}</td>
                        <td class="px-4 py-3">
                            @if ($cabinet->statut === 'actif')
                                <form method="POST" action="{{ route('admin.cabinets.suspend', $cabinet->idEntete) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="text-red-700 hover:underline">Suspendre</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('admin.cabinets.activate', $cabinet->idEntete) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="text-green-700 hover:underline">Réactiver</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-6 text-center text-gray-500">Aucun cabinet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
