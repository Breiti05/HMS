@extends('layouts.app')
@section('title', 'Anmelden')
@section('content')
<main>
    <h1>Anmelden</h1>
    <form method="POST" action="{{ route('login') }}">
        @csrf
        <label>E-Mail <input type="email" name="email" value="{{ old('email') }}" required autofocus></label>
        @error('email')<p>{{ $message }}</p>@enderror
        <label>Passwort <input type="password" name="password" required></label>
        <button type="submit">Anmelden</button>
    </form>
    <p><a href="{{ route('register') }}">Neuen Betrieb registrieren</a></p>
</main>
@endsection
