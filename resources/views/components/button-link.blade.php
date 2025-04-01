@props(['button' => []])

@if (!isset($button['role']) || auth()->user()->role->id == $button['role'])
    <a href="{{ route($button['route'], $button['data']) }}" id="{{ $button['id'] }}"
        @if ($button['tooltip_text']) data-tooltip_text="{{ $button['tooltip_text'] }}" @endif
        class="{{ $button['classes'] }}">
        {!! $button['icon'] !!}
    </a>
@endif
