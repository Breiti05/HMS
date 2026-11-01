@extends('layouts.app')
@section('title', 'Übersicht')
@section('content')
<h1 class="titel">Übersicht</h1>
<p class="unter">Lage im Betrieb auf einen Blick</p>

<div class="raster raster-4">
    <a href="{{ route('lager') }}" class="kachel" data-kachel="artikel" data-wert="{{ $artikel }}">
        <div class="etikett">Artikel</div><div class="zahl">{{ $artikel }}</div>
    </a>
    <a href="{{ route('lager') }}" class="kachel {{ $knapp ? 'warn' : 'ok' }}" data-kachel="knapp" data-wert="{{ $knapp }}">
        <div class="etikett">Unter Mindestbestand</div><div class="zahl {{ $knapp ? 'warn' : '' }}">{{ $knapp }}</div>
    </a>
    <a href="{{ route('sites.index') }}" class="kachel" data-kachel="baustellen" data-wert="{{ $baustellen }}">
        <div class="etikett">Aktive Baustellen</div><div class="zahl">{{ $baustellen }}</div>
    </a>
    <a href="{{ route('kanban') }}" class="kachel {{ $kanban ? 'warn' : '' }}" data-kachel="kanban" data-wert="{{ $kanban }}">
        <div class="etikett">Offener Nachschub</div><div class="zahl">{{ $kanban }}</div>
    </a>
</div>

<div class="raster raster-2">
    <section class="abschnitt" style="margin-top:0">
        <h2>Aktive Baustellen</h2>
        @forelse ($aktiveBaustellen as $b)
            <a href="{{ route('sites.show', $b) }}" class="kachel" style="display:block;margin-bottom:.8rem">
                <div style="display:flex;justify-content:space-between"><strong>{{ $b->name }}</strong><span class="unter">{{ $b->number }}</span></div>
                <div class="balken ok" style="margin-top:.6rem"><i style="width:{{ $b->progress() }}%"></i></div>
                <div class="unter" style="margin-top:.3rem">{{ $b->progress() }} % erledigt</div>
            </a>
        @empty
            <p class="unter">Noch keine aktive Baustelle.</p>
        @endforelse
    </section>
    <section class="abschnitt" style="margin-top:0">
        <h2>Letzte Lagerbewegungen</h2>
        <div class="kachel">
            <table class="tabelle">
                @forelse ($bewegungen as $b)
                    <tr>
                        <td>{{ $b->article->name }}</td>
                        <td>{{ $b->location->code }}</td>
                        <td style="text-align:right">{{ in_array($b->type, ['receipt', 'transfer_in']) ? '+' : '−' }}{{ rtrim(rtrim(number_format($b->quantity, 3, ',', ''), '0'), ',') }}</td>
                    </tr>
                @empty
                    <tr><td class="unter">Noch keine Buchungen.</td></tr>
                @endforelse
            </table>
        </div>
    </section>
</div>
@endsection
