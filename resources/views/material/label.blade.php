@extends('layouts.app')
@section('title', 'Etikett '.$material->article->number)
@section('content')
<main class="label">
    <div class="qr">{!! $qr !!}</div>
    <dl>
        <dt>Baustelle</dt><dd>{{ $material->site->name }}</dd>
        <dt>Baustellennummer</dt><dd>{{ $material->site->number }}</dd>
        <dt>Materialnummer</dt><dd>{{ $material->article->number }} · {{ $material->article->name }}</dd>
        <dt>Menge</dt><dd>{{ $material->quantityLabel() }} {{ $material->article->unit }}</dd>
    </dl>
</main>
@endsection
