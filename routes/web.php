<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MarketController;
use App\Http\Controllers\PetugasController;
use App\Http\Controllers\RetributionController;
use App\Http\Controllers\BendelController;
use App\Http\Controllers\VerificationController;
use App\Http\Controllers\UserController;


Route::get('/', function () {
    return redirect()->route('login');
});


Route::middleware(['auth'])->group(function () {


    // DASHBOARD
    Route::get('/dashboard', DashboardController::class)
        ->name('dashboard');


    // MASTER DATA PASAR
    Route::resource('markets', MarketController::class);


    // PETUGAS
    Route::resource('petugas', PetugasController::class);


    // RETRIBUSI
    Route::resource('retributions', RetributionController::class);


    // BENDEL DOKUMEN
    Route::get('/bendel', [BendelController::class, 'index'])
        ->name('bendel.index');


    // VERIFIKASI
    Route::get('/verifications', [VerificationController::class, 'index'])
        ->name('verifications.index');


    // USER MANAGEMENT (ADMIN ONLY)
    Route::middleware('role:admin')->group(function () {

        Route::resource('users', UserController::class);

    });


    // TEST ROLE ADMIN
    Route::get('/admin-test', function () {
        return 'Halo Admin SIPADU-DAGANG';
    })->middleware('role:admin');


});


require __DIR__.'/auth.php';