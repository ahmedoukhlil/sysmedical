@extends('layouts.admin')

@section('title', 'Tableau de bord')

@section('content')
    <h1 class="text-2xl font-bold text-gray-800 mb-6">Tableau de bord</h1>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-sm text-gray-500">Cabinets (total)</div>
            <div class="text-3xl font-bold text-gray-800 mt-1">{{ $totalCabinets }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-sm text-gray-500">Cabinets actifs</div>
            <div class="text-3xl font-bold text-green-700 mt-1">{{ $totalActifs }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-sm text-gray-500">Utilisateurs (tous cabinets)</div>
            <div class="text-3xl font-bold text-gray-800 mt-1">{{ $totalUsers }}</div>
        </div>
    </div>

    <div class="mt-6">
        <a href="{{ route('admin.cabinets.index') }}" class="text-blue-700 hover:underline">Voir la liste des cabinets →</a>
    </div>
@endsection
