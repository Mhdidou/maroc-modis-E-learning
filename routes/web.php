<?php

use App\Http\Controllers\AffectationController;
use App\Http\Controllers\ApercuController;
use App\Http\Controllers\Apprentissage\CheckpointController;
use App\Http\Controllers\Apprentissage\DevoirController;
use App\Http\Controllers\Apprentissage\LecteurController;
use App\Http\Controllers\Apprentissage\ModuleCompletionController;
use App\Http\Controllers\Apprentissage\QuizController;
use App\Http\Controllers\Builder\ChapitreController;
use App\Http\Controllers\Builder\CheckpointController as BuilderCheckpointController;
use App\Http\Controllers\Builder\FormationBuilderController;
use App\Http\Controllers\Builder\ModuleController as BuilderModuleController;
use App\Http\Controllers\Builder\QuestionController as BuilderQuestionController;
use App\Http\Controllers\CertificatController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MesFormationsController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UtilisateurController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('FactoryIndex');
})->name('home');

// Vérification publique d'un certificat par son numéro : cible du QR code, donc
// accessible sans compte (employeur, auditeur). Limitée en débit car les
// numéros sont théoriquement énumérables.
Route::get('/verifier/{numero}', [CertificatController::class, 'verifier'])
    ->middleware('throttle:20,1')
    ->name('certificats.verifier');

// Tableau de bord : aiguillé vers l'espace correspondant au rôle.
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    // Pas de DELETE /profile : un employé ne peut pas effacer son propre
    // historique de formation (pièce d'audit). L'offboarding se fait par
    // désactivation via /utilisateurs, réservée à l'admin et aux superviseurs.

    // Espace apprenant : formations attribuées + objectif du jour.
    Route::get('/mes-formations', [MesFormationsController::class, 'index'])->name('mes-formations.index');

    // Consultation d'un certificat (page imprimable) : titulaire ou admin.
    Route::get('/certificats/{certificat}', [CertificatController::class, 'show'])->name('certificats.show');
    Route::get('/certificats/{certificat}/telecharger', [CertificatController::class, 'telecharger'])
        ->name('certificats.telecharger');

    /* ------------------------------------------------------------------ */
    /*  Moteur pédagogique : endpoints de validation serveur-autoritaires */
    /*  (le client propose, le serveur dispose). Réponses JSON. */
    /* ------------------------------------------------------------------ */
    Route::prefix('apprentissage')->name('apprentissage.')->group(function () {
        // Lecteur : arbre d'une formation + état de progression (page Inertia).
        Route::get('/formations/{formation}', [LecteurController::class, 'formation'])
            ->name('formation');

        // Quiz-surprise vidéo (formatif).
        Route::post('/checkpoints/{checkpoint}', [CheckpointController::class, 'soumettre'])
            ->name('checkpoints.soumettre');

        // Complétion d'un module pdf/vidéo (préconditions vérifiées serveur).
        Route::post('/modules/{module}/terminer', [ModuleCompletionController::class, 'terminer'])
            ->name('modules.terminer');

        // Quiz noté (évaluatif) : démarrage puis soumission.
        Route::post('/modules/{module}/quiz/demarrer', [QuizController::class, 'demarrer'])
            ->name('quiz.demarrer');
        Route::post('/tentatives/{tentative}/soumettre', [QuizController::class, 'soumettre'])
            ->name('quiz.soumettre');

        // Devoirs : soumission (apprenant) et évaluation (formateur/superviseur).
        Route::post('/modules/{module}/devoir', [DevoirController::class, 'soumettre'])
            ->name('devoir.soumettre');
        Route::post('/devoirs/{devoir}/evaluer', [DevoirController::class, 'evaluer'])
            ->name('devoir.evaluer');

        // Rendu de l'apprenant : stocké sur le disque privé, donc servi par le
        // contrôleur après contrôle des droits (auteur ou correcteur).
        Route::get('/devoirs/{devoir}/fichier', [DevoirController::class, 'telecharger'])
            ->name('devoir.fichier');
    });

    // Aperçu des espaces : réservé à l'administrateur du site.
    Route::prefix('apercu')->name('apercu.')->group(function () {
        Route::get('/apprenant', [ApercuController::class, 'apprenant'])->name('apprenant');
        Route::get('/formateur', [ApercuController::class, 'formateur'])->name('formateur');
        Route::get('/superviseur', [ApercuController::class, 'superviseur'])->name('superviseur');
    });

    /* ------------------------------------------------------------------ */
    /*  Atelier formateur : construction de cours (drag-and-drop) */
    /* ------------------------------------------------------------------ */
    Route::middleware('est.formateur')->prefix('builder')->name('builder.')->group(function () {
        Route::get('/', [FormationBuilderController::class, 'index'])->name('index');
        Route::post('/formations', [FormationBuilderController::class, 'store'])->name('formations.store');
        Route::get('/formations/{formation}', [FormationBuilderController::class, 'show'])->name('show');
        Route::put('/formations/{formation}', [FormationBuilderController::class, 'updateMeta'])->name('formations.update');
        Route::post('/formations/{formation}/publier', [FormationBuilderController::class, 'publier'])->name('formations.publier');
        Route::post('/formations/{formation}/depublier', [FormationBuilderController::class, 'depublier'])->name('formations.depublier');
        Route::delete('/formations/{formation}', [FormationBuilderController::class, 'destroy'])->name('formations.destroy');

        // Chapitres
        Route::post('/formations/{formation}/chapitres', [ChapitreController::class, 'store'])->name('chapitres.store');
        Route::post('/formations/{formation}/chapitres/reordonner', [ChapitreController::class, 'reordonner'])->name('chapitres.reordonner');
        Route::put('/chapitres/{chapitre}', [ChapitreController::class, 'update'])->name('chapitres.update');
        Route::delete('/chapitres/{chapitre}', [ChapitreController::class, 'destroy'])->name('chapitres.destroy');

        // Modules
        Route::post('/chapitres/{chapitre}/modules', [BuilderModuleController::class, 'store'])->name('modules.store');
        Route::post('/chapitres/{chapitre}/modules/reordonner', [BuilderModuleController::class, 'reordonner'])->name('modules.reordonner');
        Route::post('/modules/{module}/fichier', [BuilderModuleController::class, 'televerser'])->name('modules.fichier');
        Route::put('/modules/{module}', [BuilderModuleController::class, 'update'])->name('modules.update');
        Route::delete('/modules/{module}', [BuilderModuleController::class, 'destroy'])->name('modules.destroy');

        // Checkpoints vidéo
        Route::post('/modules/{module}/checkpoints', [BuilderCheckpointController::class, 'store'])->name('checkpoints.store');
        Route::put('/checkpoints/{checkpoint}', [BuilderCheckpointController::class, 'update'])->name('checkpoints.update');
        Route::delete('/checkpoints/{checkpoint}', [BuilderCheckpointController::class, 'destroy'])->name('checkpoints.destroy');

        // Banque de questions
        Route::post('/modules/{module}/questions', [BuilderQuestionController::class, 'store'])->name('questions.store');
        Route::put('/questions/{question}', [BuilderQuestionController::class, 'update'])->name('questions.update');
        Route::delete('/questions/{question}', [BuilderQuestionController::class, 'destroy'])->name('questions.destroy');
    });

    // Gestion des comptes & affectations : admin du site et superviseurs.
    Route::middleware('peut.gerer.utilisateurs')->group(function () {
        Route::get('/utilisateurs', [UtilisateurController::class, 'index'])->name('utilisateurs.index');
        Route::get('/utilisateurs/nouveau', [UtilisateurController::class, 'create'])->name('utilisateurs.create');
        Route::post('/utilisateurs', [UtilisateurController::class, 'store'])->name('utilisateurs.store');

        // Départ / retour d'un employé : désactivation réversible, jamais de
        // suppression physique (l'historique de formation est auditable).
        Route::delete('/utilisateurs/{utilisateur}', [UtilisateurController::class, 'destroy'])
            ->name('utilisateurs.destroy');
        Route::post('/utilisateurs/{utilisateur}/reactiver', [UtilisateurController::class, 'restore'])
            ->name('utilisateurs.restore');

        // Affecter une formation précise à un apprenant précis.
        Route::get('/affectations/nouvelle', [AffectationController::class, 'create'])->name('affectations.create');
        Route::post('/affectations', [AffectationController::class, 'store'])->name('affectations.store');
    });
});

require __DIR__.'/auth.php';
