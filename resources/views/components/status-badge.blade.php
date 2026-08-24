{{--
    domain=rdv reproduit exactement les couleurs Tailwind brutes du switch statut RDV historique
    (pas les classes .status-rdv-* de app.css, qui divergent sur "En cours" : violet vs vert ici).
    Choix delibere pour zero changement visuel lors de la migration des vues existantes.

    domain=generic + prop :map sert pour tout futur statut metier (abonnement, utilisateur,
    paiement) sans etendre indefiniment ce composant. Ne pas creer de nouveau domaine dedie
    tant qu'il n'y a pas 3+ fichiers qui repetent le meme mapping.
--}}
@props([
    'status',
    'domain' => 'rdv',
    'map' => null,
])

@php
    $key = \Illuminate\Support\Str::lower(trim((string) $status));

    $rdvMap = [
        'en attente'   => ['class' => 'bg-yellow-100 text-yellow-800', 'label' => 'En Attente', 'icon' => 'fas fa-clock'],
        'confirmé'     => ['class' => 'bg-blue-100 text-blue-800', 'label' => 'Présent', 'icon' => 'fas fa-user-check'],
        'en cours'     => ['class' => 'bg-green-100 text-green-800', 'label' => 'En cours', 'icon' => 'fas fa-user-md'],
        'terminé'      => ['class' => 'bg-gray-100 text-gray-800', 'label' => 'Terminé', 'icon' => 'fas fa-check-double'],
        'annulé'       => ['class' => 'bg-red-100 text-red-800', 'label' => 'Annulé', 'icon' => 'fas fa-times'],
        'consultation' => ['class' => 'bg-purple-100 text-purple-800', 'label' => 'Consultation', 'icon' => 'fas fa-stethoscope'],
    ];

    $resolvedMap = $domain === 'rdv' ? $rdvMap : ($map ?? []);

    $entry = $resolvedMap[$key] ?? ['class' => 'bg-yellow-100 text-yellow-800', 'label' => $status ?: 'En Attente', 'icon' => 'fas fa-clock'];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ' . $entry['class']]) }}>
    @if($entry['icon'])
        <i class="{{ $entry['icon'] }} mr-1"></i>
    @endif
    {{ $entry['label'] }}
</span>
