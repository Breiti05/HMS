@extends('layouts.app')
@section('title', 'Registrieren')
@section('content')
<div class="kachel">
    <div class="brand">HMS<small>Betrieb registrieren</small></div>
    <form method="POST" action="{{ route('register') }}">
        @csrf
        <label class="feld">Betrieb <input name="company" value="{{ old('company') }}" required></label>
        @error('company')<p class="fehler">{{ $message }}</p>@enderror
        <label class="feld">Ihr Name <input name="name" value="{{ old('name') }}" required></label>
        @error('name')<p class="fehler">{{ $message }}</p>@enderror
        <label class="feld">E-Mail <input type="email" name="email" value="{{ old('email') }}" required></label>
        @error('email')<p class="fehler">{{ $message }}</p>@enderror
        <label class="feld">Passwort (mindestens 10 Zeichen) <input type="password" name="password" required></label>
        @error('password')<p class="fehler">{{ $message }}</p>@enderror
        <label class="feld">Passwort wiederholen <input type="password" name="password_confirmation" required></label>
        <button class="knopf" type="submit">Registrieren</button>
    </form>
    <p class="unter" style="margin-top:1rem"><a href="{{ route('login') }}">Schon registriert? Anmelden</a></p>
</div>
@endsection
