@props(['id', 'headers', 'data', 'fields', 'links' => []])

<table class="min-w-full bg-white" id="{{ $id }}">
    <thead class="bg-gray-100">
        <tr class="block sm:table-row">
            @foreach ($headers as $header)
                <th class="py-3 px-6 text-left block sm:table-cell font-bold text-sm text-gray-900">{{ $header }}
                </th>
            @endforeach
            @if (!empty($links))
                <th class="py-3 px-6 text-left block sm:table-cell">Acciones</th>
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
                @if (!empty($links))
                    <td class="py-3 px-6 text-left block sm:table-cell">
                        @foreach ($links as $link)
                            <a href="{{ route($link['route'], $record->id) }}" id="{{$link['id']}}"
                                @if ($link['tooltip']) data-tooltip_text="{{ $link['tooltip_text'] }}" @endif
                                class="{{ $link['classes'] }}">
                                {!! $link['icon'] !!}
                            </a>
                        @endforeach
                    </td>
                @endif
            </tr>
        @endforeach
    </tbody>
</table>
