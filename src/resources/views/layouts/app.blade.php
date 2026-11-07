<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-stone-50 text-stone-800 antialiased">
@php
    $user = auth()->user();
    $badge = ['riadh@example.com' => 'bg-teal-100 text-teal-800', 'ahmed@example.com' => 'bg-amber-100 text-amber-800'][$user->email] ?? 'bg-stone-200 text-stone-700';
@endphp
<header class="sticky top-0 z-40 bg-white/95 backdrop-blur border-b border-stone-200">
    <div class="max-w-6xl mx-auto px-4 h-14 flex items-center gap-3">
        <a href="{{ route('dashboard') }}" class="font-bold text-lg tracking-tight text-stone-900 shrink-0">📦 <span class="hidden sm:inline">{{ config('app.name') }}</span></a>
        <nav class="flex flex-1 items-center gap-1 text-sm overflow-x-auto" aria-label="Navigation principale">
            @foreach ([['pallets.index', 'pallets.*', 'Palettes'], ['items.index', 'items.*', 'Stock / Articles'], ['dashboard', 'dashboard', 'Dashboard Financier']] as [$route, $pattern, $label])
                <a href="{{ route($route) }}" class="whitespace-nowrap px-3 py-2 rounded-full {{ request()->routeIs($pattern) ? 'bg-stone-900 text-white' : 'text-stone-600 hover:bg-stone-100' }}">{{ $label }}</a>
            @endforeach
        </nav>
        <div class="flex items-center gap-2 text-sm shrink-0">
            <span class="px-2.5 py-1 rounded-full text-xs font-semibold {{ $badge }}">{{ $user->name }}</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button title="Déconnexion" aria-label="Déconnexion" class="min-h-[40px] px-3 rounded-full text-stone-500 hover:bg-stone-100 hover:text-stone-900">⏻<span class="hidden sm:inline ml-1">Quitter</span></button>
            </form>
        </div>
    </div>
</header>
<main class="max-w-6xl mx-auto px-4 py-6">
    @yield('breadcrumbs')
    @if (session('ok'))
        <div class="mb-4 p-3 rounded-xl bg-emerald-100 text-emerald-800 shadow-sm">{{ session('ok') }}</div>
    @endif
    @if ($errors->any())
        <div class="mb-4 p-3 rounded-xl bg-red-100 text-red-800 shadow-sm">
            <ul class="list-disc pl-5">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif
    @yield('content')
</main>
@stack('scripts')
</body>
</html>
