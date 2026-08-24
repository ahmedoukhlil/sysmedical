@extends('layouts.admin')

@section('title', 'Plans')

@section('content')
    <h1 class="text-2xl font-bold text-gray-800 mb-6">Plans d'abonnement</h1>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        @foreach ($plans as $plan)
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex justify-between items-start">
                    <h2 class="text-lg font-bold text-gray-800">{{ $plan->nom }}</h2>
                    @if ($plan->actif)
                        <span class="px-2 py-1 rounded bg-green-100 text-green-800 text-xs font-semibold">Actif</span>
                    @else
                        <span class="px-2 py-1 rounded bg-gray-100 text-gray-600 text-xs font-semibold">Inactif</span>
                    @endif
                </div>
                <div class="text-2xl font-bold text-blue-800 mt-2">
                    {{ number_format($plan->prix_mensuel, 0, ',', ' ') }} {{ $plan->devise }}<span class="text-sm font-normal text-gray-500">/mois</span>
                </div>
                @if ($plan->description)
                    <p class="text-sm text-gray-600 mt-2">{{ $plan->description }}</p>
                @endif
                @if ($plan->fonctionnalites)
                    <ul class="mt-4 space-y-1 text-sm text-gray-700 list-disc list-inside">
                        @foreach ($plan->fonctionnalites as $fonctionnalite)
                            <li>{{ $fonctionnalite }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endforeach
    </div>
@endsection
