@props(['items'])
<nav aria-label="Fil d'Ariane" class="mb-4 -mx-4 px-4 overflow-x-auto">
    <ol class="flex items-center gap-1 whitespace-nowrap text-sm text-stone-500">
        @foreach ($items as $label => $url)
            @php [$label, $url] = is_int($label) ? [$url, null] : [$label, $url]; @endphp
            @if (! $loop->first)<li aria-hidden="true" class="text-stone-300">›</li>@endif
            <li>
                @if ($url && ! $loop->last)
                    <a href="{{ $url }}" class="inline-flex items-center min-h-[44px] md:min-h-0 px-1 hover:text-teal-700 hover:underline">{{ $label }}</a>
                @else
                    <span class="px-1 font-medium text-stone-800" @if ($loop->last) aria-current="page" @endif>{{ $label }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
