@extends('layouts.app')
@section('breadcrumbs')
<x-breadcrumbs :items="['Palettes' => route('pallets.index'), $item->pallet->reference => route('pallets.show', $item->pallet), $item->name => ($readOnly ? route('items.show', $item) : null), ...($readOnly ? ['Lecture'] : [])]" />
@endsection
@section('content')
@php
    $c = $item->checklist;
    $input = 'w-full rounded-xl border border-slate-300 px-3 py-3 md:py-2 text-base md:text-sm';
    $money = fn ($v) => $v !== null ? number_format($v, 2, ',', ' ').' €' : '—';
    $priceRaw = $item->asking_price !== null ? rtrim(rtrim(number_format($item->asking_price, 2, '.', ''), '0'), '.') : '';
    $conditions = ['new' => 'Neuf', 'like_new' => 'Comme neuf', 'used' => 'Occasion', 'damaged' => 'Endommagé', 'unknown' => 'Inconnu'];
    $conditionLabel = $conditions[$item->condition] ?? $item->condition;
    $description = $item->listing_description;
@endphp

<div class="flex flex-col md:flex-row md:justify-between md:items-start gap-3 mb-4">
    <div>
        <div class="flex items-center gap-2 flex-wrap">
            <h1 class="text-xl md:text-2xl font-bold">{{ $item->name }}</h1>
            <x-status-badge :status="$item->status" />
        </div>
        <p class="text-slate-600 text-sm mt-1">
            Palette <a class="text-blue-700 hover:underline" href="{{ route('pallets.show', $item->pallet) }}">{{ $item->pallet->reference }}</a>
            — SKU {{ $item->original_sku ?? '—' }}
            @if ($item->internal_reference)
                — Réf. <span class="font-mono font-medium text-slate-800">{{ $item->internal_reference }}</span>
            @endif
            — Responsable : {{ $item->assignedUser?->name ?? 'Non assigné' }}
            @if ($item->supplier_product_url)
                — <a href="{{ $item->supplier_product_url }}" target="_blank" rel="noopener noreferrer" class="text-blue-700 hover:underline">Fiche vendeur ↗</a>
            @endif
        </p>
    </div>
    <div class="flex flex-wrap gap-2">
        @if ($readOnly)
            <a href="{{ route('items.show', $item) }}" class="flex-1 md:flex-none text-center px-4 py-3 md:py-2 bg-slate-900 text-white rounded-xl">✏️ Mode édition</a>
        @else
            <a href="{{ route('items.show', ['item' => $item, 'mode' => 'lecture']) }}" class="flex-1 md:flex-none text-center px-4 py-3 md:py-2 bg-white border border-slate-300 rounded-xl">👁 Mode lecture</a>
            <button type="button" onclick="document.getElementById('history-modal').showModal()" class="flex-1 md:flex-none text-center px-4 py-3 md:py-2 bg-white border border-slate-300 rounded-xl">🕘 Historique</button>
            <form method="POST" action="{{ route('items.destroy', $item) }}" onsubmit="return confirm('Supprimer cet article ?')" class="flex-1 md:flex-none">
                @csrf @method('DELETE')<button class="w-full px-4 py-3 md:py-2 bg-red-600 text-white rounded-xl">Supprimer</button>
            </form>
        @endif
    </div>
</div>

@unless ($readOnly)
<form method="POST" action="{{ route('items.update', $item) }}" enctype="multipart/form-data" class="mb-6">
    @csrf @method('PUT')
    @include('items._form', ['pallet' => $item->pallet])
</form>

<div class="bg-white p-4 md:p-6 rounded-2xl shadow-sm text-sm mb-6">
    <h2 class="font-semibold mb-3">Workflow</h2>
    <div class="flex flex-wrap gap-2 mb-2">
        @foreach ($item->status->next() as $next)
            <form method="POST" action="{{ route('items.transition', $item) }}">
                @csrf @method('PATCH')
                <input type="hidden" name="status" value="{{ $next->value }}">
                <button class="px-4 py-3 md:px-3 md:py-1.5 bg-slate-900 text-white rounded-xl active:bg-slate-700">→ {{ $next->label() }}</button>
            </form>
        @endforeach
    </div>
    <p class="text-xs text-slate-500 mb-5">Pour passer à « Vendu », renseignez le prix final puis enregistrez le formulaire.</p>

    <h3 class="font-semibold mb-2">Checklist</h3>
    <form method="POST" action="{{ route('items.inspection', $item) }}" class="space-y-3 md:max-w-xl">
        @csrf @method('PUT')
        <label class="block">Emballage
            <select name="package_condition" class="{{ $input }}">
                <option value="">—</option>
                @foreach (['intact' => 'Intact', 'damaged' => 'Abîmé', 'missing' => 'Absent'] as $v => $l)
                    <option value="{{ $v }}" @selected(old('package_condition', $c?->package_condition) === $v)>{{ $l }}</option>
                @endforeach
            </select>
        </label>
        <label class="block">Fonctionnel
            <select name="is_functional" class="{{ $input }}">
                @php $f = old('is_functional', $c?->is_functional === null ? '' : (int) $c->is_functional); @endphp
                <option value="" @selected($f === '')>Non testé</option>
                <option value="1" @selected((string) $f === '1')>Oui</option>
                <option value="0" @selected((string) $f === '0')>Non</option>
            </select>
        </label>
        <label class="block">Éléments manquants<textarea name="missing_parts_details" rows="2" class="{{ $input }}">{{ old('missing_parts_details', $c?->missing_parts_details) }}</textarea></label>
        <label class="flex items-center gap-2"><input type="checkbox" name="cleaned" value="1" class="h-5 w-5" @checked(old('cleaned', $c?->cleaned))> Nettoyage effectué</label>
        <button class="w-full md:w-auto px-4 py-3 md:py-1.5 bg-slate-900 text-white rounded-xl">Enregistrer la checklist</button>
        @if ($c?->inspected_at)<p class="text-xs text-slate-500">Dernière inspection : {{ $c->inspected_at->format('d/m/Y H:i') }}</p>@endif
    </form>
</div>

<dialog id="history-modal" class="w-[calc(100%-2rem)] max-w-2xl rounded-2xl p-0 shadow-xl backdrop:bg-slate-900/50 m-auto">
    <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3 md:px-6">
        <h2 class="font-semibold">Historique des modifications</h2>
        <button type="button" onclick="this.closest('dialog').close()" class="px-3 py-2 rounded-lg hover:bg-slate-100" aria-label="Fermer">✕</button>
    </div>
    <ol class="relative border-l border-slate-200 ml-6 mr-4 my-5 space-y-5 max-h-[70vh] overflow-y-auto pr-2">
        @forelse ($item->histories as $history)
            <li class="ml-5">
                <span class="absolute -left-1.5 mt-1.5 h-3 w-3 rounded-full bg-slate-400 ring-4 ring-white"></span>
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1">
                    <span class="text-sm font-medium text-slate-800">
                        {{ match ($history->action) {
                            'created' => 'Article créé',
                            'status_changed' => 'Changement de statut',
                            default => 'Modification',
                        } }}
                    </span>
                    <time class="text-xs text-slate-500" datetime="{{ $history->created_at->toIso8601String() }}">
                        {{ $history->created_at->format('d/m/Y à H:i') }}
                    </time>
                </div>
                <p class="mt-1 text-sm text-slate-600 whitespace-pre-line break-words">{{ $history->description }}</p>
                <p class="mt-1 text-xs text-slate-500">Par {{ $history->user?->name ?? 'Utilisateur supprimé / système' }}</p>
            </li>
        @empty
            <li class="ml-5 text-sm text-slate-500">Aucune modification enregistrée.</li>
        @endforelse
    </ol>
</dialog>
@endunless

@if ($readOnly)
{{-- Mode lecture / copie rapide --}}
<section class="bg-white rounded-2xl shadow-sm p-4 md:p-6 mb-6 ring-1 ring-emerald-200">
    <div class="flex items-center justify-between mb-4">
        <h2 class="font-semibold">📋 Mode copie rapide (annonce)</h2>
        @if ($item->marketplace_url)
            <a href="{{ $item->marketplace_url }}" target="_blank" rel="noopener noreferrer" class="text-sm text-blue-700 hover:underline">Voir l'annonce ↗</a>
        @endif
    </div>

    <div class="md:grid md:grid-cols-3 md:gap-6">
        @if ($item->image_url)
            <img src="{{ $item->image_url }}" alt="{{ $item->name }}" class="w-full max-h-72 md:max-h-none rounded-xl object-cover mb-4 md:mb-0 md:col-span-1">
        @endif
        <div class="space-y-5 {{ $item->image_url ? 'md:col-span-2' : 'md:col-span-3' }}">
            <div>
                <div class="text-xs uppercase tracking-wide text-slate-500 mb-1">Titre</div>
                <div class="flex items-center gap-3">
                    <p class="flex-1 text-lg font-semibold break-words">{{ $item->name }}</p>
                    <x-copy-button :text="$item->name" />
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <div class="text-xs uppercase tracking-wide text-slate-500 mb-1">État</div>
                    <p class="font-medium">{{ $conditionLabel }}</p>
                </div>
                <div>
                    <div class="text-xs uppercase tracking-wide text-slate-500 mb-1">Prix affiché</div>
                    <div class="flex items-center gap-3">
                        <p class="text-lg font-semibold">{{ $money($item->asking_price) }}</p>
                        @if ($priceRaw !== '')<x-copy-button :text="$priceRaw" />@endif
                    </div>
                </div>
            </div>
            <div>
                <div class="flex items-center justify-between gap-3 mb-1">
                    <div class="text-xs uppercase tracking-wide text-slate-500">Description</div>
                    @if ($description !== '')<x-copy-button :text="$description" label="Copier le texte" />@endif
                </div>
                @if ($description !== '')
                    <p class="whitespace-pre-line rounded-xl bg-slate-50 p-3 text-sm break-words">{{ $description }}</p>
                @else
                    <p class="text-sm text-slate-500">Aucune description — <a class="text-blue-700 hover:underline" href="{{ route('items.show', $item) }}">ajouter</a>.</p>
                @endif
            </div>
        </div>
    </div>
</section>

<div class="grid md:grid-cols-2 gap-6">
    <div class="bg-white p-4 md:p-6 rounded-2xl shadow-sm text-sm space-y-1.5">
        <h2 class="font-semibold mb-2">Finances</h2>
        <div class="flex justify-between"><span class="text-slate-500">MSRP estimé</span><span>{{ $money($item->estimated_msrp) }}</span></div>
        <div class="flex justify-between"><span class="text-slate-500">Prix d'achat unitaire</span><span>{{ $money($item->purchase_cost_share) }}</span></div>
        <div class="flex justify-between"><span class="text-slate-500">Prix affiché</span><span>{{ $money($item->asking_price) }}@if ($item->expected_margin !== null) <span class="text-slate-400">(marge visée {{ $money($item->expected_margin) }})</span>@endif</span></div>
        <div class="flex justify-between"><span class="text-slate-500">Prix plancher</span><span>{{ $money($item->bottom_price) }}</span></div>
        <div class="flex justify-between"><span class="text-slate-500">Mise en vente</span><span>{{ $item->listing_date?->format('d/m/Y') ?? '—' }}</span></div>
        <div class="flex justify-between"><span class="text-slate-500">Vendu le</span><span>{{ $item->sale_date?->format('d/m/Y') ?? '—' }}</span></div>
        <div class="flex justify-between"><span class="text-slate-500">Prix final</span><span>{{ $money($item->final_sale_price) }}</span></div>
        <div class="flex justify-between font-semibold pt-1 border-t"><span>Marge brute</span>
            @if ($item->margin !== null)
                <span class="{{ $item->margin >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ $money($item->margin) }} ({{ $item->margin_percent ?? '—' }} %)</span>
            @else <span>—</span> @endif
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
    async function copyText(text) {
        if (navigator.clipboard && window.isSecureContext) {
            return navigator.clipboard.writeText(text);
        }
        // Repli (HTTP / anciens navigateurs mobiles)
        const ta = document.createElement('textarea');
        ta.value = text; ta.style.position = 'fixed'; ta.style.opacity = '0';
        document.body.appendChild(ta); ta.select();
        const ok = document.execCommand('copy');
        ta.remove();
        if (!ok) throw new Error('copy failed');
    }

    document.querySelectorAll('.copy-btn').forEach(btn => {
        btn.addEventListener('click', async () => {
            const span = btn.querySelector('span');
            try {
                await copyText(btn.dataset.copy);
                span.textContent = 'Copié ✓';
                btn.classList.replace('bg-slate-900', 'bg-emerald-600');
            } catch {
                span.textContent = 'Échec';
                btn.classList.replace('bg-slate-900', 'bg-red-600');
            }
            setTimeout(() => {
                span.textContent = btn.dataset.label;
                btn.classList.remove('bg-emerald-600', 'bg-red-600');
                btn.classList.add('bg-slate-900');
            }, 1800);
        });
    });
</script>
@endpush
