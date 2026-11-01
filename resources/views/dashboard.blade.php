@extends('layouts.app')
@section('title', 'Übersicht')
@section('content')
<main>
    <h1>{{ auth()->user()->tenant->name }}</h1>
</main>
@endsection
