@props([
    'paginator',
    'action',
    'preservedQuery' => [],
])

@php
    $currentPageSize = $paginator->perPage();
    $pageSizes = [10, 20, 50, 100];

    if (! in_array($currentPageSize, $pageSizes, true)) {
        $pageSizes[] = $currentPageSize;
        sort($pageSizes);
    }
@endphp

<form method="GET" action="{{ $action }}" class="flex flex-wrap items-center gap-2 text-sm">
    @foreach ($preservedQuery as $name => $value)
        @if ($name !== 'page' && $name !== 'per_page' && $value !== null)
            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
        @endif
    @endforeach
    <label for="pagination-per-page" class="text-slate-600">Éléments par page</label>
    <select id="pagination-per-page" name="per_page" class="rounded-xl border border-slate-300 bg-white px-3 py-2">
        @foreach ($pageSizes as $pageSize)
            <option value="{{ $pageSize }}" @selected($currentPageSize === $pageSize)>{{ $pageSize }}</option>
        @endforeach
    </select>
    <button type="submit" class="rounded-xl border border-slate-300 px-3 py-2 text-slate-700 hover:bg-slate-50">Appliquer</button>
</form>