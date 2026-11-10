<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-stone-50 font-sans text-stone-800 antialiased">
@php
    $user = auth()->user();
    $badge = ['riadh@example.com' => 'bg-teal-100 text-teal-800', 'ahmed@example.com' => 'bg-amber-100 text-amber-800'][$user->email] ?? 'bg-stone-200 text-stone-700';
@endphp
<header class="sticky top-0 z-40 border-b border-stone-200 bg-white/95 backdrop-blur">
    <div class="relative mx-auto max-w-6xl px-4">
        <div class="flex min-h-16 items-center justify-between gap-4">
            <x-app-logo
                :href="route('dashboard')"
                class="shrink-0 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-stone-500 focus-visible:ring-offset-2"
            />

            <button
                type="button"
                class="inline-flex size-11 shrink-0 items-center justify-center rounded-xl border border-stone-200 bg-white text-stone-700 shadow-sm transition hover:bg-stone-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-stone-500 focus-visible:ring-offset-2 lg:hidden"
                data-menu-toggle
                aria-controls="primary-navigation"
                aria-expanded="false"
                aria-label="Ouvrir le menu"
            >
                <svg data-menu-open-icon class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true">
                    <path d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
                <svg data-menu-close-icon class="hidden size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true">
                    <path d="m6 6 12 12M18 6 6 18"/>
                </svg>
            </button>

            <div
                id="primary-navigation"
                class="absolute left-0 right-0 top-full z-50 hidden border-b border-stone-200 bg-white px-4 pb-4 shadow-lg lg:static lg:z-auto lg:ml-8 lg:flex lg:flex-1 lg:items-center lg:justify-between lg:border-0 lg:bg-transparent lg:p-0 lg:shadow-none"
            >
                <nav class="flex flex-col gap-1 py-3 text-sm lg:flex-row lg:items-center lg:gap-1 lg:py-0" aria-label="Navigation principale">
                    @foreach ([['pallets.index', 'pallets.*', 'Palettes'], ['items.index', 'items.*', 'Stock / Articles'], ['dashboard', 'dashboard', 'Dashboard Financier']] as [$route, $pattern, $label])
                        <a
                            href="{{ route($route) }}"
                            class="rounded-xl px-3 py-2.5 transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-stone-500 lg:rounded-full lg:py-2 {{ request()->routeIs($pattern) ? 'bg-stone-900 text-white' : 'text-stone-600 hover:bg-stone-100 hover:text-stone-900' }}"
                            @if (request()->routeIs($pattern)) aria-current="page" @endif
                        >{{ $label }}</a>
                    @endforeach
                </nav>

                <div class="flex items-center justify-between gap-3 border-t border-stone-100 pt-3 text-sm lg:border-0 lg:pt-0">
                    <span class="max-w-[12rem] truncate rounded-full px-2.5 py-1 text-xs font-semibold {{ $badge }}">{{ $user->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button
                            type="submit"
                            class="inline-flex min-h-10 items-center gap-2 rounded-full px-3 py-2 text-stone-600 transition hover:bg-stone-100 hover:text-stone-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-stone-500"
                        >
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M10 17l5-5-5-5M15 12H3"/>
                                <path d="M12 3h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6"/>
                            </svg>
                            <span>Quitter</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>
<main class="mx-auto max-w-6xl px-4 py-6 sm:py-8">
    @yield('breadcrumbs')
    @if (session('ok'))
        <div class="mb-4 rounded-xl bg-emerald-100 p-3 text-emerald-800 shadow-sm">{{ session('ok') }}</div>
    @endif
    @if ($errors->any())
        <div class="mb-4 rounded-xl bg-red-100 p-3 text-red-800 shadow-sm">
            <ul class="list-disc pl-5">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif
    @yield('content')
</main>
@stack('scripts')
</body>
</html>
