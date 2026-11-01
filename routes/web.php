<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\MaterialLabelController;
use App\Http\Controllers\ScanController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/anmelden', [LoginController::class, 'create'])->name('login');
    Route::post('/anmelden', [LoginController::class, 'store']);
    Route::get('/registrieren', [RegisterController::class, 'create'])->name('register');
    Route::post('/registrieren', [RegisterController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('/abmelden', [LoginController::class, 'destroy'])->name('logout');
    Route::view('/', 'dashboard')->name('dashboard');
    Route::post('/scan', [ScanController::class, 'store'])->name('scan');
    Route::get('/material/{material}/etikett', [MaterialLabelController::class, 'show'])->name('material.label');
});
