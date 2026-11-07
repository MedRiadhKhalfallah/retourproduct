@extends('layouts.app')
@section('breadcrumbs')
<x-breadcrumbs :items="['Palettes' => route('pallets.index'), 'Nouvelle palette']" />
@endsection
@section('content')
<h1 class="text-2xl font-bold mb-4">Nouvelle palette</h1>
<form method="POST" action="{{ route('pallets.store') }}" enctype="multipart/form-data" class="bg-white p-4 rounded shadow">
    @csrf
    @include('pallets._form')
</form>
@endsection
