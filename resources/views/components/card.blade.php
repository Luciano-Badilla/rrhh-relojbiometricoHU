@props(['title', 'content', 'icon', 'clickeable' => false, 'route' => null, 'class' => ''])

<a @if ($clickeable) href="{{ $route }}" @endif
    {{ $attributes->merge(['class' => 'estado relative block overflow-hidden bg-white border border-black rounded-xl p-3 hover:text-black w-25 shadow-sm ' . $class]) }}>

    <div class="flex flex-row items-center justify-between pb-2">
        <h2 class="text-md font-bold">{{ $title }}</h2>
        <i class="{{ $icon }} h-4 w-4 text-gray-500"></i>
    </div>
    <div>
        <div class="text-sm font-semibold text-gray-500">
            {{ $content }}
        </div>
    </div>
</a>
