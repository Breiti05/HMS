@extends('layouts.app')
@section('title', 'Baustellen')
@section('content')
<h1 class="titel">Baustellen</h1>
<p class="unter">Aufgabenstand und Material je Baustelle</p>

<div class="raster raster-2">
    @forelse ($baustellen as $b)
        <a href="{{ route('sites.show', $b) }}" class="kachel {{ $b->status === 'active' ? '' : 'ok' }}" data-baustelle="{{ $b->number }}" data-fortschritt="{{ $b->progress() }}">
            <div style="display:flex;justify-content:space-between;gap:1rem">
                <strong>{{ $b->name }}</strong><span class="unter">{{ $b->number }}</span>
            </div>
            <div class="unter">{{ $b->address }}</div>
            <div class="balken ok" style="margin-top:.8rem"><i style="width:{{ $b->progress() }}%"></i></div>
            <div class="unter" style="margin-top:.35rem;display:flex;justify-content:space-between">
                <span>{{ $b->progress() }} % erledigt</span>
                <span>Material: {{ $b->materials->whereNotNull('loaded_at')->count() }} / {{ $b->materials->count() }} verladen</span>
            </div>
        </a>
    @empty
        <p class="unter">Noch keine Baustelle angelegt.</p>
    @endforelse
</div>

<section class="abschnitt">
    <h2>Neue Baustelle</h2>
    <form class="kachel" method="POST" action="{{ route('sites.store') }}">
        @csrf
        <div class="formzeile">
            <label class="feld">Nummer <input name="number" value="{{ old('number') }}" required></label>
            <label class="feld">Bezeichnung <input name="name" value="{{ old('name') }}" required></label>
            <label class="feld">Adresse <input name="address" value="{{ old('address') }}"></label>
            <button class="knopf" type="submit">Anlegen</button>
        </div>
        @foreach (['number', 'name', 'address'] as $f)@error($f)<p class="fehler">{{ $message }}</p>@enderror @endforeach
    </form>
</section>
@endsection
