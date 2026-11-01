@extends('layouts.app')
@section('title', 'Registrieren')
@section('content')
<main>
    <h1>Betrieb registrieren</h1>
    <form method="POST" action="{{ route('register') }}">
        @csrf
        <label>Betrieb <input name="company" value="{{ old('company') }}" required></label>
        @error('company')<p>{{ $message }}</p>@enderror
        <label>Name <input name="name" value="{{ old('name') }}" required></label>
        <label>E-Mail <input type="email" name="email" value="{{ old('email') }}" required></label>
        @error('email')<p>{{ $message }}</p>@enderror
        <label>Passwort <input type="password" name="password" required></label>
        @error('password')<p>{{ $message }}</p>@enderror
        <label>Passwort wiederholen <input type="password" name="password_confirmation" required></label>
        <button type="submit">Registrieren</button>
    </form>
    <p><a href="{{ route('login') }}">Schon registriert? Anmelden</a></p>
</main>
@endsection
