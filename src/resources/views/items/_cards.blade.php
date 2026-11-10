@php
    $money = fn ($value) => $value !== null ? number_format($value, 2, ',', ' ').' €' : '—';
@endphp
<div class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4 lg:grid-cols-4">
    @forelse ($items as $item)
        <article class="group flex min-w-0 flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-1 hover:border-slate-400 hover:shadow-md">
            <a href="{{ route('items.show', $item) }}" aria-label="Ouvrir la fiche de {{ $item->name }}" class="grid min-w-0 grid-cols-[5.5rem_minmax(0,1fr)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-blue-600 sm:block">
                <div class="relative aspect-square bg-slate-100">
                    @if ($item->image_url)
                        <img src="{{ $item->image_url }}" alt="{{ $item->name }}" class="h-full w-full object-contain p-3" loading="lazy">
                    @else
                        <div class="flex h-full items-center justify-center text-4xl text-slate-400" aria-hidden="true">📦</div>
                    @endif
                    <span class="absolute left-3 top-3 rounded-full bg-slate-900/80 px-2.5 py-1 text-xs font-semibold text-white">ID {{ $item->id }}</span>
                </div>
                <div class="p-3 sm:p-4">
                    @if ($item->original_sku || $item->internal_reference)
                        <div class="mb-2 flex flex-wrap gap-1.5 text-xs">
                            @if ($item->original_sku)
                                <span class="max-w-full truncate rounded-md bg-slate-100 px-2 py-1 text-slate-600" title="SKU {{ $item->original_sku }}">SKU {{ $item->original_sku }}</span>
                            @endif
                            @if ($item->internal_reference)
                                <span class="max-w-full truncate rounded-md bg-slate-100 px-2 py-1 text-slate-600" title="Référence {{ $item->internal_reference }}">Réf. {{ $item->internal_reference }}</span>
                            @endif
                        </div>
                    @endif
                    <h2 class="line-clamp-2 min-h-10 text-sm font-semibold text-slate-900 group-hover:text-blue-700 sm:text-base">{{ $item->name }}</h2>
                    @if ($showPallet)
                        <p class="mt-2 truncate text-xs text-slate-500">Palette {{ $item->pallet->reference }}</p>
                    @endif
                    <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
                        <x-status-badge :status="$item->status" />
                        <span class="text-sm font-semibold text-slate-900">{{ $money($item->asking_price) }}</span>
                    </div>
                </div>
            </a>
            <dl class="mt-auto grid grid-cols-2 gap-x-3 gap-y-2 border-t border-slate-100 px-3 py-3 text-xs sm:px-4">
                <div>
                    <dt class="text-slate-500">Coût</dt>
                    <dd class="font-medium text-slate-800">{{ $money($item->purchase_cost_share) }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">Plancher</dt>
                    <dd class="font-medium text-slate-800">{{ $money($item->bottom_price) }}</dd>
                </div>
                <div class="min-w-0">
                    <dt class="text-slate-500">Responsable</dt>
                    <dd class="truncate font-medium text-slate-800">{{ $item->assignedUser?->name ?? 'Non assigné' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">Marge</dt>
                    <dd class="font-medium {{ $item->margin === null ? 'text-slate-800' : ($item->margin >= 0 ? 'text-emerald-600' : 'text-red-600') }}">
                        @if ($item->margin !== null)
                            {{ $money($item->margin) }} ({{ $item->margin_percent ?? '—' }} %)
                        @else
                            —
                        @endif
                    </dd>
                </div>
            </dl>
            <div class="flex items-center justify-between gap-2 px-3 pb-3 text-sm sm:px-4">
                <a href="{{ route('items.show', $item) }}" class="font-semibold text-blue-700 hover:underline">Voir la fiche →</a>
                @if ($item->marketplace_url)
                    <a href="{{ $item->marketplace_url }}" target="_blank" rel="noopener noreferrer" class="text-slate-600 hover:text-slate-900">Annonce ↗</a>
                @endif
            </div>
        </article>
    @empty
        <div class="col-span-full rounded-2xl bg-white px-4 py-12 text-center text-sm text-slate-500 shadow-sm">
            Aucun article ne correspond à ces critères.
        </div>
    @endforelse
</div>
