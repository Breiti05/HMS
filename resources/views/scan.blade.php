@extends('layouts.app')
@section('title', 'Verladescan')
@section('content')
<h1 class="titel">Verladescan</h1>
<p class="unter">Etikett vor die Kamera halten. Beim Scan wird das Material aus dem Lager gebucht und gilt als verladen.</p>

<div style="margin-top:1.25rem">
    <div id="scanner"></div>
    <div id="ergebnis" class="ergebnis" hidden></div>
    <form id="manuell" style="margin-top:1rem" class="formzeile">
        <label class="feld">Oder Etiketteninhalt eingeben <input name="payload" placeholder="HMS1|…" autocomplete="off"></label>
        <button class="knopf leise" type="submit">Senden</button>
    </form>
</div>
@endsection
