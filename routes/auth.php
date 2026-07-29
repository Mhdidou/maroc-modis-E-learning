<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use Illuminate\Support\Facades\Route;

// NB : l'inscription publique est volontairement désactivée.
// Seuls l'admin du site et les superviseurs créent des comptes
// (voir /utilisateurs, protégé par le middleware `peut.gerer.utilisateurs`).
Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');

    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->name('password.store');
});

// NB : pas de vérification d'e-mail. Les comptes sont créés par un gestionnaire
// qui saisit lui-même l'adresse ; le schéma `utilisateurs` n'a donc pas de
// colonne `email_verified_at` et le modèle n'implémente pas MustVerifyEmail.
// Les routes Breeze correspondantes ont été retirées : elles levaient une erreur
// (appel de hasVerifiedEmail() sur un modèle qui ne l'implémente pas).
Route::middleware('auth')->group(function () {
    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
