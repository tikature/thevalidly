<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\CertificateBatchController;
use App\Http\Controllers\CertificateVerificationController;
use App\Http\Controllers\BackgroundLibraryController;

/*
|--------------------------------------------------------------------------
| Publik
|--------------------------------------------------------------------------
*/
Route::get('/', fn() => view('landing'))->name('landing');

// Verifikasi sertifikat — halaman web publik (NO download)
Route::get('/verify/{token}', [CertificateController::class, 'verify'])->name('certificate.verify');

// Halaman peserta publik (ADA download PDF)
Route::get('/cert/{token}', [CertificateController::class, 'participant'])->name('certificate.participant');

// Halaman batch publik
Route::get('/batch/{batch_token}', [CertificateBatchController::class, 'show'])->name('certificate.batch.show');

/*
|--------------------------------------------------------------------------
| API Publik — Verifikasi QR Code (Iterasi 4)
|
| Endpoint ini dipanggil ketika seseorang scan QR code yang tercetak
| di sudut kiri bawah PDF sertifikat. Mengembalikan JSON berisi data
| sertifikat beserta status validitasnya.
|
| Contoh URL QR: https://validly.app/api/verify/{uuid}
|--------------------------------------------------------------------------
*/
Route::get('/api/verify/{token}', [CertificateVerificationController::class, 'apiVerify'])
    ->name('certificate.verify.api');

/*
|--------------------------------------------------------------------------
| Auth
|--------------------------------------------------------------------------
*/
Route::get('/login',   [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login',  [LoginController::class, 'login'])->name('login.post');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

/*
|--------------------------------------------------------------------------
| Admin Lembaga
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:admin'])->prefix('dashboard')->group(function () {

    // Generator
    Route::get('/', [CertificateController::class, 'index'])->name('certificate.index');

    // Individual store + PDF
    Route::post('/certificates',             [CertificateController::class, 'store'])->name('certificate.store');
    Route::post('/certificates/bulk',        [CertificateController::class, 'storeBulk'])->name('certificate.storeBulk');
    Route::get('/certificates/{token}/pdf',  [CertificateController::class, 'pdf'])->name('certificate.pdf');
    Route::post('/certificates/pregenerate/{token}', [CertificateController::class, 'pregenerate'])->name('certificate.pregenerate');
    Route::delete('/certificates/{certificate}', [CertificateController::class, 'destroy'])->name('certificate.destroy');

    // Batch processing (queue)
    Route::post('/certificates/batch',                         [CertificateBatchController::class, 'store'])->name('certificate.batch.store');
    Route::get('/certificates/batch/{token}/progress',         [CertificateBatchController::class, 'progress'])->name('certificate.batch.progress');
    Route::get('/certificates/batch/{token}/certs',            [CertificateBatchController::class, 'certificates'])->name('certificate.batch.certs');
    Route::get('/certificates/batch/{token}/zip',              [CertificateBatchController::class, 'downloadZip'])->name('certificate.batch.zip');

    // Batch history
    Route::get('/history/batch',                    [CertificateController::class, 'historyBatch'])->name('certificate.history.batch');
    Route::get('/history/batch/{batchId}/detail',   [CertificateBatchController::class, 'detail'])->name('certificate.batch.detail');
    Route::delete('/history/batch/{batchId}',        [CertificateBatchController::class, 'destroyBatch'])->name('certificate.batch.destroy');

    // Riwayat individual
    Route::get('/history', [CertificateController::class, 'history'])->name('certificate.history');

    // Aset lembaga
    Route::post('/assets/upload', [CertificateController::class, 'uploadAsset'])->name('certificate.asset.upload');
    Route::post('/assets/remove', [CertificateController::class, 'removeAsset'])->name('certificate.asset.remove');
    Route::get('/assets',         [CertificateController::class, 'getAssets'])->name('certificate.asset.get');

    // Background Library
    Route::get('/backgrounds/library',              [BackgroundLibraryController::class, 'index'])  ->name('background.library.index');
    Route::post('/backgrounds/library',             [BackgroundLibraryController::class, 'store'])  ->name('background.library.store');
    Route::delete('/backgrounds/library/{background}', [BackgroundLibraryController::class, 'destroy'])->name('background.library.destroy');
    Route::post('/backgrounds/library/{background}/select', [BackgroundLibraryController::class, 'select'])->name('background.library.select');

    // Profil
    Route::get('/profile',  [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

/*
|--------------------------------------------------------------------------
| Super Admin
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:super_admin'])->prefix('superadmin')->group(function () {
    Route::get('/', [SuperAdminController::class, 'index'])->name('superadmin.index');

    Route::post('/institutions',                       [SuperAdminController::class, 'storeInstitution'])->name('superadmin.institutions.store');
    Route::patch('/institutions/{institution}',        [SuperAdminController::class, 'updateInstitution'])->name('superadmin.institutions.update');
    Route::patch('/institutions/{institution}/toggle', [SuperAdminController::class, 'toggleInstitution'])->name('superadmin.institutions.toggle');
    Route::delete('/institutions/{institution}',       [SuperAdminController::class, 'destroyInstitution'])->name('superadmin.institutions.destroy');

    Route::post('/institutions/{institution}/admins',  [SuperAdminController::class, 'storeAdmin'])->name('superadmin.admins.store');
    Route::patch('/admins/{user}',                     [SuperAdminController::class, 'updateAdmin'])->name('superadmin.admins.update');
    Route::delete('/admins/{user}',                    [SuperAdminController::class, 'destroyAdmin'])->name('superadmin.admins.destroy');
    
    Route::post('/superadmins',          [SuperAdminController::class, 'storeSuperAdmin'])->name('superadmin.superadmins.store');
    Route::delete('/superadmins/{user}', [SuperAdminController::class, 'destroySuperAdmin'])->name('superadmin.superadmins.destroy');
});
