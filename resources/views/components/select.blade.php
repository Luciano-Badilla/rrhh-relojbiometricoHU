@props([
    'options' => [], // Array dinámico de opciones
    'placeholder' => 'Seleccionar una opción', // Placeholder predeterminado
    'name' => '', // Nombre del select
    'id' => '', // ID del select
    'selected' => [], // Valores seleccionados (array para múltiples opciones)
    'multiple' => false, // Si es un select múltiple
])

<div>
    <label for="{{ $id }}" {{ $attributes->merge(['class' => 'block font-medium text-sm text-gray-700']) }}>
        {{ $slot }}
    </label>
    <select name="{{ $name }}{{ $multiple ? '[]' : '' }}" 
            id="{{ $id }}" 
            {{ $multiple ? 'multiple' : '' }} 
            {{ $attributes->merge(['class' => 'p-2 h-10 mt-1 block w-full border-gray-300 rounded-md shadow-sm']) }}>
        @if($placeholder && !$multiple)
            <option value="" {{ empty($selected) ? 'selected' : '' }}>{{ $placeholder }}</option>
        @endif
        @foreach($options as $value => $label)
            <option value="{{ $value }}" {{ in_array((string) $value, (array) $selected) ? 'selected' : '' }}>
                {{ $label }}
            </option>
        @endforeach
    </select>
</div>
