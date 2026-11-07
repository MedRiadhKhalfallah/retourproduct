@extends('layouts.app')
@section('content')
@php $profit = $revenue - $investment; @endphp
<h1 class="text-2xl font-bold mb-4">Dashboard</h1>

<div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
    @foreach ([
        ['Investissement', number_format($investment, 2).' €', ''],
        ['Revenu généré', number_format($revenue, 2).' €', ''],
        ['Profit net', number_format($profit, 2).' €', $profit >= 0 ? 'text-green-600' : 'text-red-600'],
        ['À vérifier', $toVerify, 'text-amber-600'],
        ['Prêts à vendre', $ready, 'text-blue-600'],
    ] as [$label, $value, $cls])
        <div class="bg-white rounded shadow p-4">
            <div class="text-sm text-gray-500">{{ $label }}</div>
            <div class="text-xl font-semibold {{ $cls }}">{{ $value }}</div>
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
    <form method="GET" action="{{ route('dashboard') }}" class="mb-4 grid gap-2 sm:grid-cols-[minmax(0,1fr)_auto_auto]">
        <input type="search" name="search" value="{{ $filters['search'] ?? '' }}"
            placeholder="Nom, SKU, annonce ou fiche vendeur…"
            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200">
        <select name="assigned_to" class="rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm">
            <option value="">Tous les responsables</option>
            <option value="unassigned" @selected(($filters['assigned_to'] ?? '') === 'unassigned')>Non assigné</option>
            @foreach ($assignees as $assignee)
                <option value="{{ $assignee->id }}" @selected((string) ($filters['assigned_to'] ?? '') === (string) $assignee->id)>{{ $assignee->name }}</option>
            @endforeach
        </select>
        <button class="rounded-xl bg-slate-900 px-5 py-3 text-sm font-medium text-white hover:bg-slate-700">Rechercher</button>
    </form>
    @include('items._table', ['items' => $items, 'showPallet' => true])
    <div class="mt-4">{{ $items->links() }}</div>
</section>

<h2 class="text-lg font-semibold mb-2">Rentabilité par palette</h2>
<div class="overflow-x-auto rounded-2xl shadow-sm"><table class="w-full bg-white text-sm">
    <thead class="bg-gray-50 text-left">
        <tr><th class="p-2">Palette</th><th class="p-2">Fournisseur</th><th class="p-2 text-right">Investi</th><th class="p-2 text-right">Revenu</th><th class="p-2 text-right">Profit</th><th class="p-2 text-right">ROI</th></tr>
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
</table></div>
@endsection
