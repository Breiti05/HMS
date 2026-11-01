@extends('layouts.app')
@section('title', 'Anmelden')
@section('content')
<div class="kachel">
    <div class="brand">HMS<small>Handwerker-Management</small></div>
    <form method="POST" action="{{ route('login') }}">
        @csrf
        <label class="feld">E-Mail <input type="email" name="email" value="{{ old('email') }}" required autofocus></label>
        @error('email')<p class="fehler">{{ $message }}</p>@enderror
        <label class="feld">Passwort <input type="password" name="password" required></label>
        <button class="knopf" type="submit">Anmelden</button>
    </form>
    <p class="unter" style="margin-top:1rem"><a href="{{ route('register') }}">Neuen Betrieb registrieren</a></p>
</div>
@endsection
