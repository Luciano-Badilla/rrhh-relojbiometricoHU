@props(['id', 'class', 'show', 'title', 'subtitle'])

<div class="{{ 'modal fade ' }}" id="{{ $id }}" tabindex="-1" aria-labelledby="{{ $id }}"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 8px !important">
            <div class="modal-header border-transparent">
                <div class="flex flex-col w-full">
                    <div class="flex justify-between">
                        <h5 class="modal-title" id="exampleModalLabel">{{ $title }}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><i
                                class="fa-solid fa-xmark"></i></button>
                    </div>
                    <p class="text-muted">{{ $subtitle }}</p>
                </div>
            </div>
            <div class="mb-3 border-transparent">
                {{ $slot }}
            </div>
        </div>
    </div>
</div>
