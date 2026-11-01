@extends('layouts.app')
@section('title', 'Lager')
@section('content')
<h1 class="titel">Lager</h1>
<p class="unter">Regale und Lagerplätze. Gelb markiert: Bestand unter Mindestmenge.</p>

@forelse ($lager as $l)
    @php $spalten = max(1, (int) $l->locations->max('col')); @endphp
    <section class="abschnitt">
        <h2>{{ $l->name }} @if ($l->type === 'vehicle')<span class="marke">Fahrzeug</span>@endif</h2>
        <div class="regal" style="grid-template-columns: repeat({{ $spalten }}, minmax(120px, 1fr))">
            @foreach ($l->locations as $p)
                @php
                    $mitBestand = $p->levels->filter(fn ($s) => (float) $s->quantity > 0);
                    $status = $mitBestand->isEmpty() ? 'leer'
                        : ($mitBestand->contains(fn ($s) => in_array($s->article_id, $knappeArtikel)) ? 'knapp' : 'ok');
                @endphp
                <div class="platz" data-platz="{{ $p->code }}" data-status="{{ $status }}" style="grid-column: {{ $p->col }}; grid-row: {{ $p->row }}">
                    <div class="code">{{ $p->code }}</div>
                    @forelse ($mitBestand as $s)
                        <div class="inhalt">{{ $s->article->name }} · {{ rtrim(rtrim(number_format($s->quantity, 3, ',', ''), '0'), ',') }} {{ $s->article->unit }}</div>
                    @empty
                        <div class="leer">leer</div>
                    @endforelse
                </div>
            @endforeach
        </div>
    </section>
@empty
    <p class="unter" style="margin-top:1rem">Noch kein Lager angelegt.</p>
@endforelse

<section class="abschnitt">
    <h2>Buchen</h2>
    <form class="kachel" method="POST" action="{{ route('stock.store') }}">
        @csrf
        <div class="formzeile">
            <label class="feld">Vorgang
                <select name="type"><option value="receipt">Wareneingang</option><option value="issue">Warenausgang</option></select>
            </label>
            <label class="feld">Artikel
                <select name="article_id" required>@foreach ($artikel as $a)<option value="{{ $a->id }}" @selected(old('article_id') == $a->id)>{{ $a->number }} · {{ $a->name }}</option>@endforeach</select>
            </label>
            <label class="feld">Lagerplatz
                <select name="storage_location_id" required>@foreach ($plaetze as $p)<option value="{{ $p->id }}" @selected(old('storage_location_id') == $p->id)>{{ $p->warehouse->name }} · {{ $p->code }}</option>@endforeach</select>
            </label>
            <label class="feld">Menge <input name="quantity" type="number" step="0.001" min="0.001" value="{{ old('quantity') }}" required></label>
            <label class="feld">Notiz <input name="note" maxlength="200" value="{{ old('note') }}"></label>
            <button class="knopf" type="submit">Buchen</button>
        </div>
        @foreach (['article_id', 'storage_location_id', 'quantity', 'type'] as $f)@error($f)<p class="fehler">{{ $message }}</p>@enderror @endforeach
    </form>
</section>
@endsection
