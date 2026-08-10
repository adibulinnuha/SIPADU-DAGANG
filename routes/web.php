<?php

use App\Http\Controllers\BackupController;
use App\Http\Controllers\BendelController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EretDashboardController;
use App\Http\Controllers\MarketController;
use App\Http\Controllers\OcrController;
use App\Http\Controllers\PetugasController;
use App\Http\Controllers\RekapHarianController;
use App\Http\Controllers\RetributionController;
use App\Http\Controllers\RetributionsExportController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VerificationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| ROUTE UTAMA
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | DASHBOARD
    |--------------------------------------------------------------------------
    */
    Route::get('/dashboard', DashboardController::class)
        ->name('dashboard');

    Route::post('/dashboard/eret/save', [EretDashboardController::class, 'save'])
        ->name('dashboard.eret.save');

    /*
    |--------------------------------------------------------------------------
    | OCR e-Ticketing
    |--------------------------------------------------------------------------
    */
    Route::get('/ocr', [OcrController::class, 'index'])
        ->name('ocr.index');

    Route::post('/ocr/process', [OcrController::class, 'process'])
        ->name('ocr.process');

    Route::get('/ocr/review', [OcrController::class, 'review'])
        ->name('ocr.review');

    Route::post('/ocr/store', [OcrController::class, 'store'])
        ->name('ocr.store');

    /*
    |--------------------------------------------------------------------------
    | MASTER DATA PASAR
    |--------------------------------------------------------------------------
    */
    Route::resource('markets', MarketController::class);

    /*
    |--------------------------------------------------------------------------
    | PETUGAS
    |--------------------------------------------------------------------------
    */
    Route::resource('petugas', PetugasController::class)
        ->parameters(['petugas' => 'petugas']);

    // Endpoint dropdown Juru Pungut aktif per pasar (dipakai dashboard ERET)
    Route::get('/markets/{market}/active-petugas', [PetugasController::class, 'activePetugas'])
        ->name('markets.active-petugas');

    /*
    |--------------------------------------------------------------------------
    | RETRIBUSI
    |--------------------------------------------------------------------------
    */
    Route::get('/retributions/export-template', [RetributionsExportController::class, 'template'])
        ->name('retributions.export-template');

    Route::get('/retributions/export', [RetributionController::class, 'export'])
        ->name('retributions.export');

    Route::resource('retributions', RetributionController::class);

    /*
    |--------------------------------------------------------------------------
    | REKAP HARIAN
    |--------------------------------------------------------------------------
    */
    Route::get('/rekap-harian', [RekapHarianController::class, 'index'])
        ->name('rekap-harian.index');

    Route::get('/rekap-harian/export', [RekapHarianController::class, 'export'])
        ->name('rekap-harian.export');

    /*
    |--------------------------------------------------------------------------
    | BENDEL
    |--------------------------------------------------------------------------
    */
    Route::get('/bendel', [BendelController::class, 'index'])
        ->name('bendel.index');

    Route::post('/bendel/generate', [BendelController::class, 'generate'])
        ->name('bendels.generate');

    /*
    |--------------------------------------------------------------------------
    | VERIFIKASI
    |--------------------------------------------------------------------------
    */
    Route::resource('verifications', VerificationController::class);

    /*
    |--------------------------------------------------------------------------
    | USER MANAGEMENT
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:admin')->group(function () {

        Route::resource('users', UserController::class);

        /*
        |--------------------------------------------------------------------------
        | BACKUP & RESTORE (Admin only)
        |--------------------------------------------------------------------------
        */
        Route::get('/backups', [BackupController::class, 'index'])
            ->name('backup.index');

        Route::post('/backups', [BackupController::class, 'create'])
            ->name('backup.create');

        Route::get('/backups/{filename}/download', [BackupController::class, 'download'])
            ->name('backup.download');

        Route::delete('/backups/{filename}', [BackupController::class, 'destroy'])
            ->name('backup.destroy');

        Route::post('/backups/{filename}/restore', [BackupController::class, 'restore'])
            ->name('backup.restore');
    });

    Route::get('/admin-test', function () {
        return 'Halo Admin SIPADU-DAGANG';
    })->middleware('role:admin');
});

require __DIR__.'/auth.php';