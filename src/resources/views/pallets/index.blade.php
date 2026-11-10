@extends('layouts.app')
@section('content')
<div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <h1 class="text-xl font-bold sm:text-2xl">Palettes</h1>
    <a href="{{ route('pallets.create') }}" class="w-full rounded-xl bg-slate-900 px-4 py-3 text-center text-sm font-medium text-white transition hover:bg-slate-700 sm:w-auto sm:py-2">+ Nouvelle palette</a>
</div>
<p class="mb-3 text-sm leading-relaxed text-slate-500">
    Le profit reflète les ventes déjà réalisées moins le coût total ; il peut rester négatif tant que la palette contient des articles à vendre.
</p>
<div class="overflow-x-auto overscroll-x-contain rounded-2xl shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-600" role="region" aria-label="Liste des palettes" tabindex="0">
    <table class="min-w-[44rem] w-full bg-white text-sm">
    <thead class="bg-gray-50 text-left">
        <tr><th class="p-2">Référence</th><th class="p-2">Fournisseur</th><th class="p-2">Date</th><th class="p-2 text-right">Coût</th><th class="p-2 text-right">Articles</th><th class="p-2 text-right">Profit</th></tr>
    </thead>
    <tbody>
    @forelse ($pallets as $p)
        <tr class="border-t">
            <td class="p-2"><a class="text-blue-600 hover:underline" href="{{ route('pallets.show', $p) }}">{{ $p->reference }}</a></td>
            <td class="p-2">{{ $p->supplier_name }}</td>
            <td class="p-2">{{ $p->purchase_date->format('d/m/Y') }}</td>
            <td class="p-2 text-right">{{ number_format($p->total_cost, 2) }} €</td>
            <td class="p-2 text-right">{{ $p->items_count }}</td>
            <td class="p-2 text-right {{ $p->profit >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ number_format($p->profit, 2) }} €</td>
        </tr>
    @empty
        <tr><td class="p-4 text-gray-500" colspan="6">Aucune palette.</td></tr>
    @endforelse
    </tbody>
    </table>
</div>
<div class="mt-4">{{ $pallets->links() }}</div>
@endsection
