<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KanbanController;
use App\Http\Controllers\MaterialLabelController;
use App\Http\Controllers\ScanController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\SiteMaterialController;
use App\Http\Controllers\SiteTaskController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\WarehouseController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/anmelden', [LoginController::class, 'create'])->name('login');
    Route::post('/anmelden', [LoginController::class, 'store']);
    Route::get('/registrieren', [RegisterController::class, 'create'])->name('register');
    Route::post('/registrieren', [RegisterController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('/abmelden', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::get('/lager', [WarehouseController::class, 'index'])->name('lager');
    Route::post('/lager/buchen', [StockController::class, 'store'])->name('stock.store');

    Route::get('/baustellen', [SiteController::class, 'index'])->name('sites.index');
    Route::post('/baustellen', [SiteController::class, 'store'])->name('sites.store');
    Route::get('/baustellen/{site}', [SiteController::class, 'show'])->name('sites.show');
    Route::post('/baustellen/{site}/aufgaben', [SiteTaskController::class, 'store'])->name('tasks.store');
    Route::post('/aufgaben/{task}/umschalten', [SiteTaskController::class, 'toggle'])->name('tasks.toggle');
    Route::post('/baustellen/{site}/material', [SiteMaterialController::class, 'store'])->name('materials.store');
    Route::get('/material/{material}/etikett', [MaterialLabelController::class, 'show'])->name('material.label');

    Route::get('/kanban', [KanbanController::class, 'index'])->name('kanban');
    Route::post('/kanban/{card}/status', [KanbanController::class, 'status'])->name('kanban.status');

    Route::get('/scan', [ScanController::class, 'show'])->name('scan.page');
    Route::post('/scan', [ScanController::class, 'store'])->name('scan');
});
