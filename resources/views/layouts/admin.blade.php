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

    <!-- Conteneur de toasts -->
    <div id="toast-container" aria-live="polite" aria-atomic="true"></div>

    @livewireScripts

    <script>
        window.showToast = function(message, type = 'info', duration = 4000) {
            const container = document.getElementById('toast-container');
            if (!container) return;
            const icons = { success:'fa-check-circle', error:'fa-exclamation-circle', warning:'fa-exclamation-triangle', info:'fa-info-circle' };
            const colors = { success:'#16a34a', error:'#dc2626', warning:'#d97706', info:'var(--color-primary)' };
            const t = document.createElement('div');
            t.className = `toast toast-${type}`;
            t.innerHTML = `<i class="fas ${icons[type]||icons.info}" style="color:${colors[type]||colors.info};flex-shrink:0"></i><span>${message}</span>`;
            container.appendChild(t);
            setTimeout(() => { t.classList.add('toast-hide'); setTimeout(() => t.remove(), 220); }, duration);
        };

        document.addEventListener('livewire:load', function() {
            Livewire.on('toast', ({ message, type }) => window.showToast(message, type || 'info'));
        });
    </script>
</body>
</html>
