@extends('layouts.app')
@section('title', 'Etikett '.$material->article->number)
@section('content')
<p class="unter kein-druck"><a href="{{ route('sites.show', $material->site_id) }}">← Baustelle</a> · <a href="#" onclick="window.print();return false">Drucken</a></p>
<div class="etikettdruck">
    <div class="qr">{!! $qr !!}</div>
    <dl>
        <dt>Baustelle</dt><dd>{{ $material->site->name }}</dd>
        <dt>Baustellennr.</dt><dd>{{ $material->site->number }}</dd>
        <dt>Materialnr.</dt><dd>{{ $material->article->number }} · {{ $material->article->name }}</dd>
        <dt>Menge</dt><dd>{{ $material->quantityLabel() }} {{ $material->article->unit }}</dd>
    </dl>
</div>
@endsection
