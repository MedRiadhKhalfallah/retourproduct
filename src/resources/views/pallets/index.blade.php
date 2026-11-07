@extends('layouts.app')
@section('content')
<div class="flex justify-between items-center mb-4">
    <h1 class="text-2xl font-bold">Palettes</h1>
    <a href="{{ route('pallets.create') }}" class="px-3 py-2 bg-slate-800 text-white rounded">+ Nouvelle palette</a>
</div>
<div class="overflow-x-auto rounded-2xl shadow-sm"><table class="w-full bg-white text-sm">
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
</table></div>
<div class="mt-4">{{ $pallets->links() }}</div>
@endsection
