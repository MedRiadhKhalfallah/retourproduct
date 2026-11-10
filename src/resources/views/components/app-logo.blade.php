@props(['href'])

<a href="{{ $href }}" {{ $attributes->class(['inline-flex min-w-0 items-center gap-3 rounded-xl text-stone-900']) }}>
    <span class="grid size-10 shrink-0 place-items-center rounded-2xl bg-stone-900 text-white shadow-sm">
        <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="m5 7.5 7-3.5 7 3.5-7 3.5-7-3.5Z"/>
            <path d="M5 7.5v5L12 16l7-3.5v-5M12 11v5"/>
            <path d="M3.5 18h17M5 18v2.5M12 18v2.5M19 18v2.5"/>
        </svg>
    </span>
    <span class="min-w-0">
        <span class="block truncate text-sm font-bold tracking-tight sm:text-base">{{ config('app.name') }}</span>
        <span class="hidden text-xs font-medium text-stone-500 sm:block">Gestion de palettes</span>
    </span>
</a>
