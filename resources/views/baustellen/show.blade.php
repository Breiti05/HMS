@extends('layouts.app')
@section('title', $site->name)
@section('content')
<p class="unter"><a href="{{ route('sites.index') }}">← Baustellen</a></p>
<h1 class="titel">{{ $site->name }} <span class="unter">{{ $site->number }}</span></h1>
<p class="unter">{{ $site->address }}</p>

<div class="kachel" style="margin-top:1rem" data-fortschritt="{{ $site->progress() }}">
    <div class="etikett">Fortschritt</div>
    <div class="balken ok" style="margin-top:.6rem"><i style="width:{{ $site->progress() }}%"></i></div>
    <div class="unter" style="margin-top:.3rem">{{ $site->progress() }} % der Aufgaben erledigt</div>
</div>

<div class="raster raster-2">
    <section class="abschnitt" style="margin-top:0">
        <h2>Aufgaben</h2>
        <div class="kachel">
            @forelse ($site->tasks as $t)
                <form method="POST" action="{{ route('tasks.toggle', $t) }}" class="aufgabe {{ $t->done_at ? 'erledigt' : '' }}">
                    @csrf
                    <button class="haken" type="submit" aria-label="Aufgabe umschalten">✓</button>
                    <span class="text">{{ $t->title }}</span>
                </form>
            @empty
                <p class="unter">Noch keine Aufgaben.</p>
            @endforelse
            <form method="POST" action="{{ route('tasks.store', $site) }}" class="formzeile" style="margin-top:1rem">
                @csrf
                <label class="feld">Neue Aufgabe <input name="title" required></label>
                <button class="knopf" type="submit">Hinzufügen</button>
            </form>
            @error('title')<p class="fehler">{{ $message }}</p>@enderror
        </div>
    </section>

    <section class="abschnitt" style="margin-top:0">
        <h2>Material</h2>
        <div class="kachel">
            <table class="tabelle">
                <tr><th>Artikel</th><th>Menge</th><th>Status</th><th></th></tr>
                @forelse ($site->materials as $m)
                    @php $verladen = (bool) $m->loaded_at; @endphp
                    <tr>
                        <td>{{ $m->article->number }} · {{ $m->article->name }}</td>
                        <td>{{ $m->quantityLabel() }} {{ $m->article->unit }}</td>
                        <td><span class="marke {{ $verladen ? 'ok' : 'warn' }}" data-material-status="{{ $verladen ? 'verladen' : 'offen' }}">{{ $verladen ? 'verladen' : 'offen' }}</span></td>
                        <td>@unless ($verladen)<a href="{{ route('material.label', $m) }}" target="_blank">Etikett</a>@endunless</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="unter">Noch kein Material zugeordnet.</td></tr>
                @endforelse
            </table>
            <form method="POST" action="{{ route('materials.store', $site) }}" style="margin-top:1rem">
                @csrf
                <div class="formzeile">
                    <label class="feld">Artikel
                        <select name="article_id" required>@foreach ($artikel as $a)<option value="{{ $a->id }}">{{ $a->number }} · {{ $a->name }}</option>@endforeach</select>
                    </label>
                    <label class="feld">Aus Lagerplatz
                        <select name="storage_location_id" required>@foreach ($plaetze as $p)<option value="{{ $p->id }}">{{ $p->code }}</option>@endforeach</select>
                    </label>
                    <label class="feld">Menge <input name="quantity" type="number" step="0.001" min="0.001" required></label>
                    <button class="knopf" type="submit">Zuordnen</button>
                </div>
                @foreach (['article_id', 'storage_location_id', 'quantity'] as $f)@error($f)<p class="fehler">{{ $message }}</p>@enderror @endforeach
            </form>
        </div>
    </section>
</div>
@endsection
