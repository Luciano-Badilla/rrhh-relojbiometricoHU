@props(['button' => []])

@if(!isset($button['role']) || auth()->user()->role->id == $button['role'])
    <button id="{{ $button['id'] }}" 
        @isset($button['type']) type="{{ $button['type'] }}" @endisset
        @isset($button['tooltip_text']) data-tooltip_text="{{ $button['tooltip_text'] }}" @endisset
        @isset($button['modal_id']) data-toggle="modal" data-target="#{{ $button['modal_id'] }}" @endisset
        class="{{ $button['classes'] }}" data-button-id="{{ $button['id'] }}">

        <span class="button-icon">{!! $button['icon'] !!} @isset($button['text']) {{ $button['text'] }} @endisset</span>
        @isset($button['loading'])
            <div class="spinner-border spinner-border-sm hidden my-1 updating-icon" role="status">
                <span class="sr-only">Loading...</span>
            </div>
        @endisset
    </button>
@endif

<script>
    document.addEventListener("DOMContentLoaded", function() {
        document.querySelectorAll("button[data-button-id]").forEach(button => {
            button.addEventListener("click", function() {
                let icon = button.querySelector(".button-icon");
                let updatingIcon = button.querySelector(".updating-icon");

                if (icon && updatingIcon) {
                    icon.classList.add("hidden");
                    updatingIcon.classList.remove("hidden");
                }
            });
        });
    });
</script>
