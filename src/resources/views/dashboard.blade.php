@extends('layouts.app')
@section('content')
@php $profit = $revenue - $investment; @endphp
<h1 class="mb-4 text-xl font-bold sm:text-2xl">Dashboard</h1>

<div class="mb-8 grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4 lg:grid-cols-5">
    @foreach ([
        ['Investissement', number_format($investment, 2).' €', ''],
        ['Revenu généré', number_format($revenue, 2).' €', ''],
        ['Profit net', number_format($profit, 2).' €', $profit >= 0 ? 'text-green-600' : 'text-red-600'],
        ['À vérifier', $toVerify, 'text-amber-600'],
        ['Prêts à vendre', $ready, 'text-blue-600'],
    ] as [$label, $value, $cls])
        <div class="min-w-0 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-sm text-slate-500">{{ $label }}</div>
            <div class="mt-1 text-xl font-semibold {{ $cls }}">{{ $value }}</div>
            @if ($label === 'Profit net')
                <p class="mt-1 text-xs leading-relaxed text-slate-500">
                    Basé sur les ventes réalisées ; il peut rester négatif tant que le stock n'est pas entièrement vendu.
                </p>
            @endif
        </div>
    @endforeach
</div>
<p class="mb-6 text-sm text-slate-600">Articles vendus : <strong>{{ $sold }}</strong></p>

<div class="flex flex-wrap gap-2 mb-8">
    @foreach (\App\Enums\ItemStatus::cases() as $s)
        <a href="{{ route('items.index', ['status' => $s->value]) }}" class="inline-flex items-center gap-2 bg-white rounded-xl shadow-sm px-3 py-2 text-sm">
            <x-status-badge :status="$s" /> <strong>{{ $byStatus[$s->value] ?? 0 }}</strong>
        </a>
    @endforeach
</div>

<section class="mb-8">
    <div class="mb-3">
        <h2 class="text-lg font-semibold">Recherche globale des articles</h2>
        <p class="text-sm text-slate-500">Rechercher un produit et filtrer par responsable.</p>
    </div>
    <form method="GET" action="{{ route('dashboard') }}" class="mb-4 grid min-w-0 gap-2 sm:grid-cols-[minmax(0,1fr)_auto_auto]">
        <input type="search" name="search" value="{{ $filters['search'] ?? '' }}"
            placeholder="Nom, SKU, annonce ou fiche vendeur…"
            class="w-full min-w-0 rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200">
        <select name="assigned_to" class="w-full min-w-0 rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm sm:w-auto">
            <option value="">Tous les responsables</option>
            <option value="unassigned" @selected(($filters['assigned_to'] ?? '') === 'unassigned')>Non assigné</option>
            @foreach ($assignees as $assignee)
                <option value="{{ $assignee->id }}" @selected((string) ($filters['assigned_to'] ?? '') === (string) $assignee->id)>{{ $assignee->name }}</option>
            @endforeach
        </select>
        <button class="w-full rounded-xl bg-slate-900 px-5 py-3 text-sm font-medium text-white hover:bg-slate-700 sm:w-auto">Rechercher</button>
    </form>
    @include('items._table', ['items' => $items, 'showPallet' => true])
    <div class="mt-4">{{ $items->links() }}</div>
</section>

<h2 class="mb-2 text-lg font-semibold">Rentabilité par palette</h2>
<div class="overflow-x-auto overscroll-x-contain rounded-2xl shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-600" role="region" aria-label="Rentabilité par palette" tabindex="0">
    <table class="min-w-[44rem] w-full bg-white text-sm">
    <thead class="bg-gray-50 text-left">
        <tr><th class="p-2">Palette</th><th class="p-2">Fournisseur</th><th class="p-2 text-right">Investi</th><th class="p-2 text-right">Revenu</th><th class="p-2 text-right" title="Calculé avec les ventes réalisées ; il peut être négatif tant que la palette n'est pas entièrement vendue.">Profit</th><th class="p-2 text-right">ROI</th></tr>
    </thead>
    <tbody>
    @forelse ($pallets as $p)
        <tr class="border-t">
            <td class="p-2"><a class="text-blue-600 hover:underline" href="{{ route('pallets.show', $p) }}">{{ $p->reference }}</a></td>
            <td class="p-2">{{ $p->supplier_name }}</td>
            <td class="p-2 text-right">{{ number_format($p->total_cost, 2) }} €</td>
            <td class="p-2 text-right">{{ number_format($p->revenue, 2) }} €</td>
            <td class="p-2 text-right {{ $p->profit >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ number_format($p->profit, 2) }} €</td>
            <td class="p-2 text-right">{{ $p->profitability !== null ? $p->profitability.' %' : '—' }}</td>
        </tr>
    @empty
        <tr><td class="p-4 text-gray-500" colspan="6">Aucune palette.</td></tr>
    @endforelse
    </tbody>
    </table>
</div>
@endsection
