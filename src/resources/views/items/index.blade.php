@extends('layouts.app')
@section('breadcrumbs')
<x-breadcrumbs :items="['Stock / Articles']" />
@endsection
@section('content')
@php
    $current = request('assigned_to');
    $currentStatus = request('status');
@endphp
<div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <h1 class="text-xl font-bold md:text-2xl">Stock / Articles</h1>
        <p class="mt-1 text-sm text-slate-500">Retrouvez rapidement une fiche par nom, identifiant ou SKU.</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        <p class="text-sm text-slate-500" aria-live="polite">
            {{ $items->total() }} {{ $items->total() > 1 ? 'articles' : 'article' }}
        </p>
        <a href="{{ route('items.export') }}" class="rounded-xl bg-slate-900 px-4 py-3 text-center text-sm text-white hover:bg-slate-700 sm:py-2">
            Exporter tous les articles (.txt)
        </a>
    </div>
</div>
<form method="GET" action="{{ route('items.index') }}" role="search" aria-label="Rechercher et filtrer les articles" class="mb-4 flex flex-col gap-3 rounded-2xl bg-white p-3 shadow-sm sm:flex-row sm:items-center">
    @if ($current)<input type="hidden" name="assigned_to" value="{{ $current }}">@endif
    <div class="min-w-0 flex-1">
        <label for="item-search" class="sr-only">Rechercher par nom, ID, SKU ou référence</label>
        <input id="item-search" type="search" name="search" value="{{ $search }}"
               placeholder="Nom, ID, SKU ou référence…"
               class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-base placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100 md:py-2 md:text-sm">
    </div>
    <select name="status" aria-label="Filtrer par statut" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-3 text-base sm:w-auto md:py-2 md:text-sm">
        <option value="">Statut : tous</option>
        @foreach (\App\Enums\ItemStatus::cases() as $status)
            <option value="{{ $status->value }}" @selected($currentStatus === $status->value)>{{ $status->label() }}</option>
        @endforeach
    </select>
    <div class="flex gap-2">
        <button class="flex-1 rounded-xl bg-slate-900 px-4 py-3 text-white sm:flex-none md:py-2">Rechercher</button>
        @if (request()->hasAny(['search', 'status', 'assigned_to']))
            <a href="{{ route('items.index') }}" class="rounded-xl border border-slate-300 px-4 py-3 text-center text-slate-600 hover:bg-slate-50 md:py-2">Réinitialiser</a>
        @endif
    </div>
</form>
<div class="mb-5 flex flex-wrap gap-2" role="group" aria-label="Filtrer par responsable">
    @foreach ([['', 'Tous'], ...$assignees->map(fn ($u) => [$u->id, $u->name])->all(), ['unassigned', 'Non assigné']] as [$value, $label])
        <a href="{{ request()->fullUrlWithQuery(['assigned_to' => $value ?: null, 'page' => null]) }}"
           class="rounded-full border px-4 py-2.5 text-sm md:py-1.5 {{ (string) $current === (string) $value ? 'border-stone-900 bg-stone-900 text-white' : 'border-stone-300 bg-white text-stone-600 hover:bg-stone-100' }}">{{ $label }}</a>
    @endforeach
</div>
@include('items._cards', ['items' => $items, 'showPallet' => true])
<div class="mt-4">{{ $items->links() }}</div>
@endsection
