@props(['id', 'headers', 'data', 'fields', 'links' => []])

<div class="rounded-2xl overflow-hidden shadow-md">
    <table class="min-w-full bg-white" id="{{ $id }}">
        <thead>
            <tr class="block sm:table-row">
                @foreach ($headers as $header)
                    <th class="py-3 px-6 text-left block sm:table-cell">{{ $header }}</th>
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
                        <td class="py-3 px-6 text-left text-md block sm:table-cell">
                            {{ $record->$field }}
                        </td>
                    @endforeach
                    @if (!empty($links))
                        <td class="py-3 px-6 text-left block sm:table-cell">
                            @foreach ($links as $link)
                                <a href="{{ route($link['route'], $record->id) }}"
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
</div>
