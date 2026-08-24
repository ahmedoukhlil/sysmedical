@props(['variant' => 'primary', 'type' => 'button'])

@php
    $variantClass = match($variant) {
        'secondary' => 'btn-secondary',
        'danger' => 'btn-danger',
        default => 'btn-primary',
    };
@endphp

<button type="{{ $type }}" {{ $attributes->merge(['class' => $variantClass]) }}>
    {{ $slot }}
</button>
