@extends('layouts.app')
@section('breadcrumbs')
<x-breadcrumbs :items="['Palettes' => route('pallets.index'), $pallet->reference]" />
@endsection
@section('content')
<div class="flex flex-col md:flex-row md:justify-between md:items-start gap-3 mb-4">
    <div>
        <h1 class="text-xl md:text-2xl font-bold">{{ $pallet->reference }}</h1>
        <p class="text-slate-600 text-sm">{{ $pallet->supplier_name }} — acheté le {{ $pallet->purchase_date->format('d/m/Y') }}</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('pallets.items.create', $pallet) }}" class="flex-1 md:flex-none text-center px-4 py-3 md:py-2 bg-slate-900 text-white rounded-xl">+ Article</a>
        <a href="{{ route('pallets.edit', $pallet) }}" class="flex-1 md:flex-none text-center px-4 py-3 md:py-2 bg-white border border-slate-300 rounded-xl">Modifier</a>
        <form method="POST" action="{{ route('pallets.destroy', $pallet) }}" onsubmit="return confirm('Supprimer la palette et ses articles ?')" class="flex-1 md:flex-none">
            @csrf @method('DELETE')<button class="w-full px-4 py-3 md:py-2 bg-red-600 text-white rounded-xl">Supprimer</button>
        </form>
    </div>
</div>

<div class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-4 mb-6">
    <div class="bg-white p-4 rounded-2xl shadow-sm"><div class="text-sm text-slate-500">Investi</div><div class="font-semibold">{{ number_format($pallet->total_cost, 2) }} €</div></div>
    <div class="bg-white p-4 rounded-2xl shadow-sm"><div class="text-sm text-slate-500">Revenu</div><div class="font-semibold">{{ number_format($pallet->revenue, 2) }} €</div></div>
    <div class="bg-white p-4 rounded-2xl shadow-sm"><div class="text-sm text-slate-500">Profit net</div><div class="font-semibold {{ $pallet->profit >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ number_format($pallet->profit, 2) }} €</div></div>
    <div class="bg-white p-4 rounded-2xl shadow-sm"><div class="text-sm text-slate-500">ROI</div><div class="font-semibold">{{ $pallet->profitability !== null ? $pallet->profitability.' %' : '—' }}</div></div>
</div>

<section class="bg-white rounded-2xl shadow-sm p-4 md:p-6 mb-6">
    <h2 class="font-semibold mb-3">📎 Pièces jointes</h2>
    <ul class="divide-y">
        @forelse ($pallet->attachments as $a)
            <li class="py-3 flex flex-col md:flex-row md:items-center gap-2 md:gap-4">
                <div class="flex items-center gap-3 min-w-0 flex-1">
                    <span class="text-2xl">{{ $a->is_pdf ? '📄' : '🖼️' }}</span>
                    <div class="min-w-0">
                        <div class="truncate font-medium text-sm">{{ $a->original_name }}</div>
                        <div class="text-xs text-slate-500">{{ $a->human_size }} · ajouté le {{ $a->created_at->format('d/m/Y') }}</div>
                    </div>
                </div>
                <div class="flex gap-2 text-sm">
                    <a href="{{ route('pallets.attachments.show', [$pallet, $a]) }}" target="_blank" rel="noopener" class="flex-1 md:flex-none text-center px-4 py-3 md:px-3 md:py-1.5 bg-white border border-slate-300 rounded-xl">Voir</a>
                    <a href="{{ route('pallets.attachments.download', [$pallet, $a]) }}" class="flex-1 md:flex-none text-center px-4 py-3 md:px-3 md:py-1.5 bg-slate-900 text-white rounded-xl">Télécharger</a>
                    <form method="POST" action="{{ route('pallets.attachments.destroy', [$pallet, $a]) }}" onsubmit="return confirm('Supprimer ce fichier ?')">
                        @csrf @method('DELETE')<button class="px-4 py-3 md:px-3 md:py-1.5 bg-red-50 text-red-700 rounded-xl" aria-label="Supprimer">🗑</button>
                    </form>
                </div>
            </li>
        @empty
            <li class="py-3 text-sm text-slate-500">Aucun fichier.</li>
        @endforelse
    </ul>
    <form method="POST" action="{{ route('pallets.attachments.store', $pallet) }}" enctype="multipart/form-data" class="mt-4 flex flex-col md:flex-row gap-2">
        @csrf
        <input type="file" name="attachments[]" multiple required accept=".jpg,.jpeg,.png,.pdf,image/*,application/pdf" class="flex-1 rounded-xl border border-slate-300 px-3 py-3 md:py-2 text-sm">
        <button class="px-4 py-3 md:py-2 bg-slate-900 text-white rounded-xl">Ajouter</button>
    </form>
</section>

@php
    $money = fn ($value) => $value === null ? '—' : number_format($value, 2, ',', ' ').' €';
@endphp
<section class="mb-6">
    <div class="mb-3">
        <h2 class="text-lg font-semibold">Rapport financier par palette</h2>
        <p class="text-sm text-slate-500">Le coût unitaire reprend la répartition au prorata du MSRP enregistrée pour chaque article. Le gain des articles non vendus est estimé au prix affiché.</p>
    </div>

    {{-- Cards mobile --}}
    <div class="space-y-3 md:hidden">
        @forelse ($financialReport['items'] as $row)
            @php
                $item = $row['item'];
                $profit = $row['profit'];
            @endphp
            <article class="rounded-2xl bg-white p-4 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <a href="{{ route('items.show', $item) }}" class="font-semibold text-blue-700 hover:underline">{{ $item->name }}</a>
                    <x-status-badge :status="$item->status" />
                </div>
                <p class="mt-1 text-xs text-slate-500">Attribué à : {{ $item->assignedUser?->name ?? 'Non assigné' }}</p>
                <dl class="mt-3 grid grid-cols-2 gap-3 text-sm">
                    <div><dt class="text-xs text-slate-500">Achat estimé</dt><dd class="font-medium">{{ $money($row['purchase_price']) }}</dd></div>
                    <div>
                        <dt class="text-xs text-slate-500">{{ $row['sale_is_estimate'] ? 'Prix cible' : 'Vente finale' }}</dt>
                        <dd class="font-medium">{{ $money($row['sale_price']) }}</dd>
                    </div>
                </dl>
                <div class="mt-3 border-t border-slate-100 pt-3">
                    <span class="text-xs text-slate-500">{{ $row['sale_is_estimate'] ? 'Gain estimé / perte estimée' : 'Gain / perte' }}</span>
                    @if ($profit !== null)
                        <div class="font-semibold {{ $profit >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                            {{ $profit >= 0 ? '+' : '' }}{{ $money($profit) }}
                        </div>
                    @else
                        <div class="font-semibold text-slate-400">—</div>
                    @endif
                </div>
            </article>
        @empty
            <p class="rounded-2xl bg-white p-4 text-sm text-slate-500 shadow-sm">Aucun article dans cette palette.</p>
        @endforelse
    </div>

    {{-- Tableau desktop --}}
    <div class="hidden overflow-x-auto rounded-2xl bg-white shadow-sm md:block">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-slate-500">
                <tr>
                    <th class="p-3">Produit</th>
                    <th class="p-3">Statut</th>
                    <th class="p-3">Attribué à</th>
                    <th class="p-3 text-right">Achat estimé</th>
                    <th class="p-3 text-right">Vente finale / cible</th>
                    <th class="p-3 text-right">Gain / perte</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($financialReport['items'] as $row)
                    @php
                        $item = $row['item'];
                        $profit = $row['profit'];
                    @endphp
                    <tr class="hover:bg-slate-50">
                        <td class="p-3"><a href="{{ route('items.show', $item) }}" class="font-medium text-blue-700 hover:underline">{{ $item->name }}</a></td>
                        <td class="p-3"><x-status-badge :status="$item->status" /></td>
                        <td class="p-3">{{ $item->assignedUser?->name ?? 'Non assigné' }}</td>
                        <td class="p-3 text-right">{{ $money($row['purchase_price']) }}</td>
                        <td class="p-3 text-right">
                            {{ $money($row['sale_price']) }}
                            @if ($row['sale_is_estimate'] && $row['sale_price'] !== null)
                                <span class="text-xs text-slate-400">(cible)</span>
                            @endif
                        </td>
                        <td class="p-3 text-right font-semibold {{ $profit === null ? 'text-slate-400' : ($profit >= 0 ? 'text-emerald-600' : 'text-rose-600') }}">
                            {{ $profit === null ? '—' : (($profit >= 0 ? '+' : '').$money($profit)) }}
                        </td>
                    </tr>
                @empty
                    <tr><td class="p-4 text-slate-500" colspan="6">Aucun article dans cette palette.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4 grid grid-cols-2 gap-3 md:grid-cols-4">
        <div class="rounded-2xl bg-white p-4 shadow-sm">
            <div class="text-xs text-slate-500">Total investi</div>
            <div class="mt-1 font-semibold">{{ $money($financialReport['purchase_total']) }}</div>
        </div>
        <div class="rounded-2xl bg-white p-4 shadow-sm">
            <div class="text-xs text-slate-500">Revenu réel (articles vendus)</div>
            <div class="mt-1 font-semibold">{{ $money($financialReport['revenue_total']) }}</div>
        </div>
        <div class="rounded-2xl bg-white p-4 shadow-sm">
            <div class="text-xs text-slate-500">Achat moyen / article</div>
            <div class="mt-1 font-semibold">{{ $money($financialReport['average_purchase_price']) }}</div>
        </div>
        <div class="rounded-2xl bg-white p-4 shadow-sm">
            <div class="text-xs text-slate-500">Vente moyenne (articles vendus)</div>
            <div class="mt-1 font-semibold">{{ $money($financialReport['average_sale_price']) }}</div>
        </div>
    </div>

    <div class="mt-3 flex flex-col gap-1 rounded-2xl border p-4 md:flex-row md:items-center md:justify-between {{ $financialReport['net_profit'] >= 0 ? 'border-emerald-200 bg-emerald-50' : 'border-rose-200 bg-rose-50' }}">
        <div>
            <div class="font-semibold">Bilan global de la palette</div>
            <div class="text-sm text-slate-600">Revenu réel {{ $money($financialReport['revenue_total']) }} − investissement {{ $money($financialReport['purchase_total']) }}</div>
        </div>
        <div class="text-xl font-bold {{ $financialReport['net_profit'] >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
            {{ $financialReport['net_profit'] >= 0 ? 'Bénéfice +' : 'Perte ' }}{{ $money($financialReport['net_profit']) }}
        </div>
    </div>
</section>
@if ($pallet->notes)<p class="mt-4 text-sm text-slate-600 whitespace-pre-line">{{ $pallet->notes }}</p>@endif
@endsection
