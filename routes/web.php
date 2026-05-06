<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CertificateController;

/*
|--------------------------------------------------------------------------
| Publik
|--------------------------------------------------------------------------
*/
Route::get('/', fn() => view('landing'))->name('landing');

// Verifikasi sertifikat (publik)
Route::get('/verify/{token}', [CertificateController::class, 'verify'])->name('certificate.verify');

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
    Route::get('/',                          [CertificateController::class, 'index'])->name('certificate.index');

    // Store & PDF
    Route::post('/certificates',             [CertificateController::class, 'store'])->name('certificate.store');
    Route::get('/certificates/{token}/pdf',  [CertificateController::class, 'pdf'])->name('certificate.pdf');
    Route::delete('/certificates/{certificate}', [CertificateController::class, 'destroy'])->name('certificate.destroy');
    Route::post('/certificate/pregenerate/{token}', [CertificateController::class, 'pregenerate'])
    ->name('certificate.pregenerate');
    
    // Riwayat
    Route::get('/history',                   [CertificateController::class, 'history'])->name('certificate.history');

    // Aset lembaga
    Route::post('/assets/upload',            [CertificateController::class, 'uploadAsset'])->name('certificate.asset.upload');
    Route::post('/assets/remove',            [CertificateController::class, 'removeAsset'])->name('certificate.asset.remove');
    Route::get('/assets',                    [CertificateController::class, 'getAssets'])->name('certificate.asset.get');

    // Profil
    Route::get('/profile',                   [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile',                 [ProfileController::class, 'update'])->name('profile.update');
});

/*
|--------------------------------------------------------------------------
| Super Admin
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:super_admin'])->prefix('superadmin')->group(function () {
    Route::get('/', [SuperAdminController::class, 'index'])->name('superadmin.index');

    // CRUD Lembaga
    Route::post('/institutions',                       [SuperAdminController::class, 'storeInstitution'])->name('superadmin.institutions.store');
    Route::patch('/institutions/{institution}',        [SuperAdminController::class, 'updateInstitution'])->name('superadmin.institutions.update');
    Route::patch('/institutions/{institution}/toggle', [SuperAdminController::class, 'toggleInstitution'])->name('superadmin.institutions.toggle');
    Route::delete('/institutions/{institution}',       [SuperAdminController::class, 'destroyInstitution'])->name('superadmin.institutions.destroy');

    // CRUD Admin per lembaga
    Route::post('/institutions/{institution}/admins',  [SuperAdminController::class, 'storeAdmin'])->name('superadmin.admins.store');
    Route::patch('/admins/{user}',                     [SuperAdminController::class, 'updateAdmin'])->name('superadmin.admins.update');
    Route::delete('/admins/{user}',                    [SuperAdminController::class, 'destroyAdmin'])->name('superadmin.admins.destroy');
});
