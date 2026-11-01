@extends('layouts.app')
@section('title', 'Kanban')
@section('content')
<h1 class="titel">Kanban-Nachschub</h1>
<p class="unter">Karten entstehen automatisch, wenn der Mindestbestand unterschritten wird.</p>

@php $spalten = ['open' => 'Offen', 'ordered' => 'Bestellt', 'delivered' => 'Geliefert']; @endphp
<div class="spalten">
    @foreach ($spalten as $key => $name)
        <section class="spalte" data-spalte="{{ $key }}">
            <h2><span>{{ $name }}</span><span>{{ ($karten[$key] ?? collect())->count() }}</span></h2>
            @foreach ($karten[$key] ?? [] as $k)
                <div class="karte">
                    <strong>{{ $k->article->name }}</strong>
                    <div class="unter">{{ $k->article->number }} · Vorschlag {{ rtrim(rtrim(number_format($k->quantity, 3, ',', ''), '0'), ',') }} {{ $k->article->unit }}</div>
                    <form method="POST" action="{{ route('kanban.status', $k) }}">
                        @csrf
                        @foreach ($spalten as $ziel => $zielName)
                            @if ($ziel !== $key)<button class="knopf leise" name="status" value="{{ $ziel }}" type="submit">→ {{ $zielName }}</button>@endif
                        @endforeach
                    </form>
                </div>
            @endforeach
        </section>
    @endforeach
</div>
@endsection
