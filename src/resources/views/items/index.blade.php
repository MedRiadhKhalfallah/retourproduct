@extends('layouts.app')
@section('breadcrumbs')
<x-breadcrumbs :items="['Stock / Articles']" />
@endsection
@section('content')
<h1 class="text-xl md:text-2xl font-bold mb-4">Stock / Articles</h1>
@php $current = request('assigned_to'); @endphp
<div class="flex flex-wrap gap-2 mb-3" role="group" aria-label="Filtrer par responsable">
    @foreach ([['', 'Tous'], ...$assignees->map(fn ($u) => [$u->id, $u->name])->all(), ['unassigned', 'Non assigné']] as [$value, $label])
        <a href="{{ request()->fullUrlWithQuery(['assigned_to' => $value ?: null, 'page' => null]) }}"
           class="px-4 py-2.5 md:py-1.5 rounded-full text-sm border {{ (string) $current === (string) $value ? 'bg-stone-900 text-white border-stone-900' : 'bg-white text-stone-600 border-stone-300 hover:bg-stone-100' }}">{{ $label }}</a>
    @endforeach
</div>
<form method="GET" class="flex gap-2 mb-4 text-base md:text-sm">
    @if ($current)<input type="hidden" name="assigned_to" value="{{ $current }}">@endif
    <select name="status" class="flex-1 md:flex-none rounded-xl border border-slate-300 px-3 py-3 md:py-2 bg-white">
        <option value="">Statut : tous</option>
        @foreach (\App\Enums\ItemStatus::cases() as $s)
            <option value="{{ $s->value }}" @selected(request('status') === $s->value)>{{ $s->label() }}</option>
        @endforeach
    </select>
    <button class="px-4 py-3 md:py-2 bg-slate-900 text-white rounded-xl">Filtrer</button>
</form>
@include('items._table', ['showPallet' => true])
<div class="mt-4">{{ $items->links() }}</div>
@endsection
