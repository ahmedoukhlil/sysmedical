<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $__cab = Auth::check() ? \App\Models\Infocabinet::find(Auth::user()->fkidcabinet ?? 0) : null;
        $__appName = ($__cab && !empty($__cab->nom_application)) ? $__cab->nom_application : 'SysMedical';
        $__logo = ($__cab && !empty($__cab->logo) && file_exists(public_path($__cab->logo))) ? asset($__cab->logo) : asset('SysMedical.png');
    @endphp
    <title>{{ $__appName }} — Mobile</title>
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#2563eb">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="SysMedical">
    <link rel="apple-touch-icon" href="/icons/icon-192.png">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="{{ asset('js/app.js') }}" defer></script>
    @livewireStyles
</head>
<body class="bg-gray-50 min-h-screen">

    <header class="bg-white shadow-sm border-b border-gray-100 px-4 py-3 flex items-center gap-3">
        <img src="{{ $__logo }}" alt="{{ $__appName }}" class="h-8 w-auto">
        <span class="font-medium text-gray-700">{{ $__appName }}</span>
    </header>

    <main class="pb-20">
        @yield('content')
    </main>

    <nav class="fixed bottom-0 inset-x-0 bg-white border-t border-gray-200 flex justify-around py-2 shadow-lg">
        <a href="{{ route('mobile.agenda') }}" class="flex flex-col items-center px-3 py-1 text-xs {{ request()->routeIs('mobile.agenda') ? 'text-primary' : 'text-gray-500' }}">
            <i class="fas fa-calendar-alt text-lg mb-1"></i>
            Agenda
        </a>
        <a href="{{ route('mobile.salle-attente') }}" class="flex flex-col items-center px-3 py-1 text-xs {{ request()->routeIs('mobile.salle-attente') ? 'text-primary' : 'text-gray-500' }}">
            <i class="fas fa-clock text-lg mb-1"></i>
            Attente
        </a>
        @if(auth()->check() && auth()->user()->hasPermission('salle-soins.view'))
            <a href="{{ route('mobile.salle-soins') }}" class="flex flex-col items-center px-3 py-1 text-xs {{ request()->routeIs('mobile.salle-soins') ? 'text-primary' : 'text-gray-500' }}">
                <i class="fas fa-user-md text-lg mb-1"></i>
                Soins
            </a>
        @endif
    </nav>

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

        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js', { scope: '/mobile/' });
            });
        }
    </script>
</body>
</html>
