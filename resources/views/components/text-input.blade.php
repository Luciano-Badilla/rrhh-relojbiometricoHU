@props([
    'id',
    'type' => 'text',
    'name' => '',
    'value' => '',
    'placeholder' => '',
    'required' => false,
    'disabled' => false,
    'class' => '', // Clases adicionales
])

<input type="{{ $type }}" id="{{ $id }}" name="{{ $name }}" value="{{ old($name, $value) }}"
    class="border-gray-300 rounded-xl shadow-sm {{ $disabled ? 'opacity-50 cursor-not-allowed' : '' }} {{ $class }}"
    placeholder="{{ $placeholder }}" {{ $required ? 'required' : '' }} {{ $disabled ? 'disabled' : '' }}
    {{ $attributes }} />

@error($name)
    <span class="text-sm text-red-500">{{ $message }}</span>
@enderror
