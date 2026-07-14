<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UtilisateurController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('FactoryIndex');
})->name('home');

// Tableau de bord : aiguillé vers l'espace correspondant au rôle.
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Gestion des comptes : réservée à l'admin du site et aux superviseurs.
    Route::middleware('peut.gerer.utilisateurs')->group(function () {
        Route::get('/utilisateurs', [UtilisateurController::class, 'index'])->name('utilisateurs.index');
        Route::get('/utilisateurs/nouveau', [UtilisateurController::class, 'create'])->name('utilisateurs.create');
        Route::post('/utilisateurs', [UtilisateurController::class, 'store'])->name('utilisateurs.store');
    });
});

require __DIR__.'/auth.php';
