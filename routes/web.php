<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MarketController;
use App\Http\Controllers\PetugasController;
use App\Http\Controllers\RetributionController;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::post('/login', [LoginController::class, 'authenticate'])
    ->name('login.authenticate');

Route::middleware('auth')->group(function () {

    Route::get('/dashboard', DashboardController::class)
        ->name('dashboard');

    Route::resource('markets', MarketController::class);

    Route::resource('petugas', PetugasController::class)
        ->parameters([
            'petugas' => 'petugas',
        ]);

    Route::resource('retributions', RetributionController::class);

    Route::view('/verifications', 'verifications.index')
        ->name('verifications.index');

    Route::view('/bendel', 'bendel.index')
        ->name('bendel.index');

    Route::view('/reports', 'reports.index')
        ->name('reports.index');

    Route::view('/backup', 'backup.index')
        ->name('backup.index');

    Route::view('/settings', 'settings.index')
        ->name('settings.index');

    Route::post('/logout', [LoginController::class, 'destroy'])
        ->name('logout');
});