@props([
    'options' => [], // Array dinámico de opciones
    'placeholder' => 'Seleccionar una opción', // Placeholder predeterminado
    'name' => '', // Nombre del select
    'id' => '', // ID del select
])

<div>
    <label for="{{ $id }}" {{ $attributes->merge(['class' => 'block font-medium text-sm text-gray-700']) }}>
        {{ $slot }}
    </label>
    <select
        name="{{ $name }}"
        id="{{ $id }}"
        {{ $attributes->merge(['class' => 'h-10 mt-1 block w-full border-gray-300 rounded-md shadow-sm']) }}
    >
        @if($placeholder)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach($options as $value => $label)
            <option value="{{ $value }}">{{ $label }}</option>
        @endforeach
    </select>
</div>
