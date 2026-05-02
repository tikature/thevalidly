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
    Route::get('/', [CertificateController::class, 'index'])->name('certificate.index');
    Route::get('/profile',   [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
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
