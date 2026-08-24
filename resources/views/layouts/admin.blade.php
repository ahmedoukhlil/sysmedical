<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin — @yield('title', 'Plateforme')</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen">
    <nav class="bg-gray-900 text-white px-6 py-3 flex justify-between items-center">
        <span class="font-bold">Admin Plateforme</span>
        <div class="flex items-center gap-4 text-sm">
            <a href="{{ route('admin.dashboard') }}" class="hover:underline">Tableau de bord</a>
            <a href="{{ route('admin.cabinets.index') }}" class="hover:underline">Cabinets</a>
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit" class="hover:underline">Déconnexion</button>
            </form>
        </div>
    </nav>
    <main class="max-w-6xl mx-auto px-6 py-8">
        @if (session('success'))
            <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
        @endif
        @yield('content')
    </main>
</body>
</html>
