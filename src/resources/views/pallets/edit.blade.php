@extends('layouts.app')
@section('breadcrumbs')
<x-breadcrumbs :items="['Palettes' => route('pallets.index'), $pallet->reference => route('pallets.show', $pallet), 'Modifier']" />
@endsection
@section('content')
<h1 class="text-2xl font-bold mb-4">Modifier {{ $pallet->reference }}</h1>
<form method="POST" action="{{ route('pallets.update', $pallet) }}" enctype="multipart/form-data" class="bg-white p-4 rounded shadow">
    @csrf @method('PUT')
    @include('pallets._form')
</form>
@endsection
