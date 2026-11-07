@php $money = fn ($v) => $v !== null ? number_format($v, 2, ',', ' ').' €' : '—'; @endphp
{{-- Mobile : cartes --}}
<div class="space-y-3 md:hidden">
    @forelse ($items as $i)
        <a href="{{ route('items.show', $i) }}" class="flex gap-3 bg-white rounded-2xl shadow-sm p-3 active:bg-slate-50">
            @if ($i->image_url)
                <img src="{{ $i->image_url }}" alt="" class="h-16 w-16 rounded-xl object-cover shrink-0">
            @else
                <div class="h-16 w-16 rounded-xl bg-slate-100 flex items-center justify-center shrink-0">📦</div>
            @endif
            <div class="min-w-0 flex-1">
                <div class="font-medium truncate">{{ $i->name }}</div>
                @if ($showPallet)<div class="text-xs text-slate-500">{{ $i->pallet->reference }}</div>@endif
                <div class="text-xs text-slate-500">Responsable : {{ $i->assignedUser?->name ?? 'Non assigné' }}</div>
                <div class="mt-1 flex items-center justify-between gap-2">
                    <x-status-badge :status="$i->status" />
                    <span class="text-sm font-semibold">{{ $money($i->asking_price) }}</span>
                </div>
            </div>
        </a>
    @empty
        <p class="p-4 text-slate-500">Aucun article.</p>
    @endforelse
</div>

{{-- Desktop : tableau compact --}}
<div class="hidden md:block bg-white rounded-2xl shadow-sm overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-left text-slate-500">
            <tr>
                <th class="p-3">Article</th>
                @if ($showPallet)<th class="p-3">Palette</th>@endif
                <th class="p-3">Responsable</th>
                <th class="p-3">Statut</th>
                <th class="p-3 text-right">Coût</th><th class="p-3 text-right">Affiché</th><th class="p-3 text-right">Plancher</th><th class="p-3 text-right">Marge</th>
                <th class="p-3">Annonce</th>
            </tr>
        </thead>
        <tbody class="divide-y">
        @forelse ($items as $i)
            <tr class="hover:bg-slate-50">
                <td class="p-3">
                    <a class="flex items-center gap-3 text-blue-700 hover:underline" href="{{ route('items.show', $i) }}">
                        @if ($i->image_url)<img src="{{ $i->image_url }}" alt="" class="h-9 w-9 rounded-lg object-cover">@endif
                        {{ $i->name }}
                    </a>
                </td>
                @if ($showPallet)<td class="p-3">{{ $i->pallet->reference }}</td>@endif
                <td class="p-3">{{ $i->assignedUser?->name ?? 'Non assigné' }}</td>
                <td class="p-3"><x-status-badge :status="$i->status" /></td>
                <td class="p-3 text-right">{{ $money($i->purchase_cost_share) }}</td>
                <td class="p-3 text-right">{{ $money($i->asking_price) }}</td>
                <td class="p-3 text-right">{{ $money($i->bottom_price) }}</td>
                <td class="p-3 text-right">
                    @if ($i->margin !== null)
                        <span class="{{ $i->margin >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ $money($i->margin) }} ({{ $i->margin_percent ?? '—' }} %)</span>
                    @else — @endif
                </td>
                <td class="p-3">
                    @if ($i->marketplace_url)<a href="{{ $i->marketplace_url }}" target="_blank" rel="noopener noreferrer" class="text-blue-700 hover:underline">Ouvrir ↗</a>@else — @endif
                </td>
            </tr>
        @empty
            <tr><td class="p-4 text-slate-500" colspan="{{ $showPallet ? 9 : 8 }}">Aucun article.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
