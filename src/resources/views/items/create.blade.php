@extends('layouts.app')
@section('breadcrumbs')
<x-breadcrumbs :items="['Palettes' => route('pallets.index'), $pallet->reference => route('pallets.show', $pallet), 'Nouvel article']" />
@endsection
@section('content')
<h1 class="text-xl md:text-2xl font-bold mb-4">Nouvel article — {{ $pallet->reference }}</h1>
<form method="POST" action="{{ route('pallets.items.store', $pallet) }}" enctype="multipart/form-data">
    @csrf
    @include('items._form')
</form>
@endsection
