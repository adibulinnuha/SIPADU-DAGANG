<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MarketController;
use App\Http\Controllers\PetugasController;
use App\Http\Controllers\RetributionController;
use App\Http\Controllers\RekapHarianController;
use App\Http\Controllers\BendelController;
use App\Http\Controllers\VerificationController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\OcrController;

/*
|--------------------------------------------------------------------------
| ROUTE OCR (TANPA LOGIN - MODE DEVELOPMENT)
|--------------------------------------------------------------------------
*/

Route::get('/ocr', [OcrController::class, 'index'])
    ->name('ocr.index');

Route::post('/ocr/process', [OcrController::class, 'process'])
    ->name('ocr.process');


/*
|--------------------------------------------------------------------------
| ROUTE UTAMA
|--------------------------------------------------------------------------
*/

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
    Route::get('/retributions/export', [RetributionController::class, 'export'])
        ->name('retributions.export');

    Route::resource('retributions', RetributionController::class);

    // REKAP HARIAN
    Route::get('/rekap-harian', [RekapHarianController::class, 'index'])
        ->name('rekap-harian.index');

    // BENDEL
    Route::get('/bendel', [BendelController::class, 'index'])
        ->name('bendel.index');

    Route::post('/bendel/generate', [BendelController::class, 'generate'])
        ->name('bendels.generate');

    // VERIFIKASI
    Route::resource('verifications', VerificationController::class);

    // USER MANAGEMENT
    Route::middleware('role:admin')->group(function () {
        Route::resource('users', UserController::class);
    });

    Route::get('/admin-test', function () {
        return 'Halo Admin SIPADU-DAGANG';
    })->middleware('role:admin');

});

require __DIR__.'/auth.php';