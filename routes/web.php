<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MarketController;
use App\Http\Controllers\PetugasController;
use App\Http\Controllers\RetributionController;
use App\Http\Controllers\TraderController;

Route::middleware('auth')->group(function () {

    Route::get('/dashboard', DashboardController::class)
        ->name('dashboard');

    Route::resource('markets', MarketController::class);
    Route::resource('petugas', PetugasController::class);
    Route::resource('retributions', RetributionController::class);
    Route::resource('traders', TraderController::class);

    // ===== TRANSAKSI =====
    Route::view('/verifications', 'verifications.index')
        ->name('verifications.index');

    // ===== LAPORAN =====
    Route::view('/bendel', 'bendel.index')
        ->name('bendel.index');

    Route::view('/reports', 'reports.index')
        ->name('reports.index');

    // ===== SISTEM =====
    Route::view('/backup', 'backup.index')
        ->name('backup.index');

    Route::view('/settings', 'settings.index')
        ->name('settings.index');

    Route::post('/logout', [LoginController::class, 'destroy'])
        ->name('logout');
});