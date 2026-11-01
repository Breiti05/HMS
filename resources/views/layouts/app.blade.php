<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'HMS') · HMS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
@auth
    <div class="shell">
        <aside class="rail">
            <div class="brand">HMS<small>{{ auth()->user()->tenant->name }}</small></div>
            <nav class="nav">
                <a href="{{ route('dashboard') }}" @class(['aktiv' => request()->routeIs('dashboard')])>Übersicht</a>
                <a href="{{ route('lager') }}" @class(['aktiv' => request()->routeIs('lager')])>Lager</a>
                <a href="{{ route('sites.index') }}" @class(['aktiv' => request()->routeIs('sites.*')])>Baustellen</a>
                <a href="{{ route('kanban') }}" @class(['aktiv' => request()->routeIs('kanban')])>Kanban</a>
                <a href="{{ route('scan.page') }}" @class(['aktiv' => request()->routeIs('scan.page')])>Verladescan</a>
                <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit">Abmelden</button></form>
            </nav>
        </aside>
        <main class="main">
            @if (session('status'))<div class="hinweis">{{ session('status') }}</div>@endif
            @yield('content')
        </main>
    </div>
@else
    <div class="gast">@yield('content')</div>
@endauth
</body>
</html>
