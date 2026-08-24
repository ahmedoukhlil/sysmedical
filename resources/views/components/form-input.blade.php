@props(['label' => null, 'name', 'type' => 'text'])

<div>
    @if($label)
        <label for="{{ $name }}" class="text-sm font-semibold text-gray-700">{{ $label }}</label>
    @endif

    <input
        type="{{ $type }}"
        name="{{ $name }}"
        id="{{ $name }}"
        {{ $attributes->merge(['class' => 'form-input']) }}
    >

    @error($name)
        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
    @enderror
</div>
