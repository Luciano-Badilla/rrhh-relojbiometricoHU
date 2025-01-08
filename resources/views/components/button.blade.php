@props(['button' => []])

<button id="{{ $button['id'] }}" type="button"
    @isset($button['tooltip']) data-tooltip_text="{{ $button['tooltip_text'] }}" @endisset
    @isset($button['modal']) data-toggle="modal" data-target="#{{ $button['modal_id'] }}" @endisset
    class="{{ $button['classes'] }}">
    {!! $button['icon'] !!}
</button>
