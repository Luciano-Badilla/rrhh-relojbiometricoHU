@props(['id', 'headers', 'data', 'fields', 'links' => [], 'buttons' => []])

<table class="min-w-full bg-white" id="{{ $id }}">
    <thead class="bg-gray-100">
        <tr class="block sm:table-row">
            @foreach ($headers as $header)
                <th class="py-3 px-6 text-left block sm:table-cell font-bold text-sm text-gray-900">{{ $header }}
                </th>
            @endforeach
            @if (!empty($links) || !empty($buttons))
                <th class="py-3 px-6 text-center block sm:table-cell">Acciones</th>
            @endif
        </tr>
    </thead>
    <tbody class="text-gray-600 text-sm font-light">
        @foreach ($data as $record)
            <tr class="border-b border-gray-200 hover:bg-gray-100">
                @foreach ($fields as $field)
                    <td class="py-3 px-6 text-left text-md block sm:table-cell font-semibold">
                        {{ $record->$field }}
                    </td>
                @endforeach
                @if (!empty($links) || !empty($buttons))
                    <td class="py-3 px-6 text-center block sm:table-cell">
                        @if (!empty($links))
                            @foreach ($links as $link)
                                <a href="{{ route($link['route'], $record->id) }}" id="{{ $link['id'] }}"
                                    @if ($link['tooltip_text']) data-tooltip_text="{{ $link['tooltip_text'] }}" @endif
                                    class="{{ $link['classes'] }}">
                                    {!! $link['icon'] !!}
                                </a>
                            @endforeach
                        @endif
                        @if (!empty($buttons))
                            @foreach ($buttons as $button)
                                @php
                                    $showButton = $button['condition'] ?? true;

                                    if (is_callable($showButton)) {
                                        $showButton = $showButton($record);
                                    }

                                    // Verificar el rol si está definido
                                    if (isset($button['role'])) {
                                        $showButton =
                                            $showButton && optional(auth()->user()->role)->id === $button['role'];
                                    }
                                @endphp

                                @if ($showButton)
                                    <button id="{{ $button['id'] }}" type="button" data-id="{{ $record->id }}"
                                        @isset($button['tooltip_text']) data-tooltip_text="{{ $button['tooltip_text'] }}" @endisset
                                        @isset($button['modal_id']) data-toggle="modal" data-target="#{{ $button['modal_id'] }}" @endisset
                                        @foreach ($button as $attr => $value)
                                    @if (Str::startsWith($attr, 'data-') && $value === true)
                                        {{ $attr }}="{{ $record->{Str::after($attr, 'data-')} }}"
                                    @endif @endforeach
                                        class="{{ $button['classes'] }}">
                                        {!! $button['icon'] !!}
                                    </button>
                                @endif
                            @endforeach
                        @endif
                    </td>
                @endif
            </tr>
        @endforeach
    </tbody>
</table>
