<?php

use App\Models\Certificat;
use App\Models\Chapitre;
use App\Models\CheckpointResolu;
use App\Models\CheckpointVideo;
use App\Models\Devoir;
use App\Models\Formation;
use App\Models\Inscription;
use App\Models\Module;
use App\Models\Progression;
use App\Models\Question;
use App\Models\ReinitialisationChapitre;
use App\Models\TentativeQuiz;
use App\Models\User;
use App\Services\MoteurCompletion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

/**
 * Construit un chapitre complet : vidéo (+ checkpoint), pdf, quiz noté (banque
 * de 4, tirage 4, seuil 70 %), et une inscription d'apprenant.
 */
function parcours(string $type = 'certifiante'): array
{
    $formateur = User::factory()->formateur()->create();
    $apprenant = User::factory()->create();

    $formation = Formation::create([
        'titre' => 'F', 'type' => $type, 'statut' => 'publie',
        'validite_mois' => 12, 'cree_par' => $formateur->id,
    ]);
    $chapitre = Chapitre::create(['formation_id' => $formation->id, 'titre' => 'C', 'position' => 0]);

    $video = Module::create(['chapitre_id' => $chapitre->id, 'type' => 'video', 'titre' => 'V', 'position' => 0, 'contenu' => 'v.mp4']);
    $cp = CheckpointVideo::create([
        'module_id' => $video->id, 'position_secondes' => 10, 'enonce' => 'Q',
        'bonne_reponse' => 'BON', 'mauvaises_reponses' => ['X', 'Y'], 'explication' => 'E',
    ]);
    $pdf = Module::create(['chapitre_id' => $chapitre->id, 'type' => 'pdf', 'titre' => 'P', 'position' => 1, 'contenu' => 'p.pdf']);
    $quiz = Module::create([
        'chapitre_id' => $chapitre->id, 'type' => 'quiz', 'titre' => 'Q', 'position' => 2,
        'nb_questions_tirees' => 4, 'seuil_reussite' => 70, 'duree_minutes' => 10,
    ]);
    foreach (range(1, 4) as $i) {
        Question::create(['module_id' => $quiz->id, 'enonce' => "Q$i", 'bonne_reponse' => "R$i", 'mauvaises_reponses' => ['a', 'b']]);
    }

    $inscription = Inscription::create([
        'utilisateur_id' => $apprenant->id, 'formation_id' => $formation->id, 'statut' => 'non_commencee',
    ]);

    return compact('formateur', 'apprenant', 'formation', 'chapitre', 'video', 'cp', 'pdf', 'quiz', 'inscription');
}

/** Termine la vidéo (checkpoint résolu) et le pdf pour rendre le quiz accessible. */
function ouvrirQuiz(array $p): void
{
    $c = app(MoteurCompletion::class);
    $pv = $c->progression($p['apprenant'], $p['video']);
    CheckpointResolu::create(['progression_id' => $pv->id, 'checkpoint_id' => $p['cp']->id, 'bonne_reponse' => true, 'resolu_le' => now()]);
    $c->terminerModule($pv);
    $c->terminerModule($c->progression($p['apprenant'], $p['pdf']));
}

/** Réponses fausses pour toutes les questions tirées d'une tentative démarrée. */
function reponsesFausses(array $questions): array
{
    return collect($questions)->mapWithKeys(fn ($q) => [$q['id'] => 'faux'])->all();
}

it('bloque la complétion de la vidéo tant qu’un checkpoint n’est pas résolu', function () {
    $p = parcours();

    $this->actingAs($p['apprenant'])
        ->postJson(route('apprentissage.modules.terminer', $p['video']))
        ->assertStatus(422);

    $this->actingAs($p['apprenant'])
        ->postJson(route('apprentissage.checkpoints.soumettre', $p['cp']), ['reponse' => 'BON'])
        ->assertOk()->assertJson(['correct' => true, 'bonne_reponse' => 'BON']);

    $this->actingAs($p['apprenant'])
        ->postJson(route('apprentissage.modules.terminer', $p['video']))
        ->assertSuccessful();

    expect(Progression::where('module_id', $p['video']->id)->first()->statut)->toBe('terminee');
});

it('enregistre le checkpoint sur mauvaise réponse sans échouer le module', function () {
    $p = parcours();

    $this->actingAs($p['apprenant'])
        ->postJson(route('apprentissage.checkpoints.soumettre', $p['cp']), ['reponse' => 'X'])
        ->assertOk()->assertJson(['correct' => false, 'bonne_reponse' => 'BON']);

    expect(CheckpointResolu::count())->toBe(1);
    expect(Progression::where('module_id', $p['video']->id)->where('statut', 'terminee')->exists())->toBeFalse();

    // Le checkpoint étant résolu (même faux), la vidéo peut désormais être terminée.
    $this->actingAs($p['apprenant'])
        ->postJson(route('apprentissage.modules.terminer', $p['video']))
        ->assertSuccessful();
});

it('réinitialise tout le chapitre après 3 échecs au quiz', function () {
    $p = parcours();
    ouvrirQuiz($p);

    expect(Progression::where('module_id', $p['video']->id)->first()->statut)->toBe('terminee');

    $reset = false;
    foreach (range(1, 3) as $n) {
        $demarrage = $this->actingAs($p['apprenant'])
            ->postJson(route('apprentissage.quiz.demarrer', $p['quiz']))
            ->assertSuccessful();

        expect($demarrage->json('data.numero'))->toBe($n);

        $sub = $this->actingAs($p['apprenant'])
            ->postJson(route('apprentissage.quiz.soumettre', $demarrage->json('data.id')), [
                'confirmation' => true,
                'reponses' => reponsesFausses($demarrage->json('questions')),
            ])->assertOk();

        $reset = $sub->json('reset_chapitre');
    }

    expect($reset)->toBeTrue();
    expect(Progression::where('module_id', $p['video']->id)->first()->statut)->toBe('non_commencee');
    expect(CheckpointResolu::count())->toBe(0);
    expect(TentativeQuiz::where('inscription_id', $p['inscription']->id)->count())->toBe(0);
    expect(ReinitialisationChapitre::where('chapitre_id', $p['chapitre']->id)->exists())->toBeTrue();

    // Le quiz est de nouveau verrouillé (prérequis réinitialisés).
    $this->actingAs($p['apprenant'])
        ->postJson(route('apprentissage.quiz.demarrer', $p['quiz']))
        ->assertStatus(422);

    // Après avoir refait la vidéo et le pdf, le compteur repart de zéro.
    ouvrirQuiz($p);
    $this->actingAs($p['apprenant'])
        ->postJson(route('apprentissage.quiz.demarrer', $p['quiz']))
        ->assertSuccessful()
        ->assertJsonPath('data.numero', 1);
});

it('un échec sous le seuil laisse des tentatives sans reset', function () {
    $p = parcours();
    ouvrirQuiz($p);

    $demarrage = $this->actingAs($p['apprenant'])
        ->postJson(route('apprentissage.quiz.demarrer', $p['quiz']))->assertSuccessful();

    $sub = $this->actingAs($p['apprenant'])
        ->postJson(route('apprentissage.quiz.soumettre', $demarrage->json('data.id')), [
            'confirmation' => true,
            'reponses' => reponsesFausses($demarrage->json('questions')),
        ])->assertOk();

    expect($sub->json('reussi'))->toBeFalse();
    expect($sub->json('reset_chapitre'))->toBeFalse();
    expect($sub->json('tentatives_restantes'))->toBe(2);
});

it('exige la case de confirmation pour soumettre un quiz', function () {
    $p = parcours();
    ouvrirQuiz($p);

    $demarrage = $this->actingAs($p['apprenant'])
        ->postJson(route('apprentissage.quiz.demarrer', $p['quiz']))->assertSuccessful();

    $this->actingAs($p['apprenant'])
        ->postJson(route('apprentissage.quiz.soumettre', $demarrage->json('data.id')), [
            'confirmation' => false,
            'reponses' => reponsesFausses($demarrage->json('questions')),
        ])->assertStatus(422);
});

it('réussit le quiz, complète la cascade et génère le certificat', function () {
    Storage::fake('local');
    $p = parcours('certifiante');
    ouvrirQuiz($p);

    $demarrage = $this->actingAs($p['apprenant'])
        ->postJson(route('apprentissage.quiz.demarrer', $p['quiz']))->assertSuccessful();

    $bonnes = collect($demarrage->json('questions'))
        ->mapWithKeys(fn ($q) => [$q['id'] => Question::find($q['id'])->bonne_reponse])
        ->all();

    $this->actingAs($p['apprenant'])
        ->postJson(route('apprentissage.quiz.soumettre', $demarrage->json('data.id')), [
            'confirmation' => true,
            'reponses' => $bonnes,
        ])->assertOk()->assertJson(['reussi' => true]);

    expect(Inscription::find($p['inscription']->id)->statut)->toBe('terminee');

    $cert = Certificat::where('formation_id', $p['formation']->id)->first();
    expect($cert)->not->toBeNull();
    expect($cert->numero_unique)->toStartWith('CERT-');
    expect($cert->expire_le)->not->toBeNull();
    Storage::disk('local')->assertExists($cert->chemin_fichier);
});

it('ne valide pas le module devoir tant qu’il n’est pas approuvé', function () {
    $formateur = User::factory()->formateur()->create();
    $apprenant = User::factory()->create();
    $formation = Formation::create(['titre' => 'D', 'type' => 'non_certifiante', 'statut' => 'publie', 'cree_par' => $formateur->id]);
    $chapitre = Chapitre::create(['formation_id' => $formation->id, 'titre' => 'C', 'position' => 0]);
    $devoirM = Module::create(['chapitre_id' => $chapitre->id, 'type' => 'devoir', 'titre' => 'Rendu', 'position' => 0, 'consignes' => 'Faire X']);
    Inscription::create(['utilisateur_id' => $apprenant->id, 'formation_id' => $formation->id, 'statut' => 'non_commencee']);

    $this->actingAs($apprenant)
        ->postJson(route('apprentissage.devoir.soumettre', $devoirM), ['contenu_texte' => 'Ma réponse'])
        ->assertSuccessful();

    $devoir = Devoir::first();
    expect($devoir->statut)->toBe('en_attente');
    expect(Progression::where('module_id', $devoirM->id)->where('statut', 'terminee')->exists())->toBeFalse();

    // Un apprenant ne peut pas évaluer.
    $this->actingAs($apprenant)
        ->postJson(route('apprentissage.devoir.evaluer', $devoir), ['decision' => 'approuve'])
        ->assertForbidden();

    // Le formateur approuve → le module se valide.
    $this->actingAs($formateur)
        ->postJson(route('apprentissage.devoir.evaluer', $devoir), ['decision' => 'approuve'])
        ->assertSuccessful();

    expect(Progression::where('module_id', $devoirM->id)->first()->statut)->toBe('terminee');
});

it('un devoir rejeté exige un commentaire et ne valide pas le module', function () {
    $formateur = User::factory()->formateur()->create();
    $apprenant = User::factory()->create();
    $formation = Formation::create(['titre' => 'D', 'type' => 'non_certifiante', 'statut' => 'publie', 'cree_par' => $formateur->id]);
    $chapitre = Chapitre::create(['formation_id' => $formation->id, 'titre' => 'C', 'position' => 0]);
    $devoirM = Module::create(['chapitre_id' => $chapitre->id, 'type' => 'devoir', 'titre' => 'Rendu', 'position' => 0]);
    Inscription::create(['utilisateur_id' => $apprenant->id, 'formation_id' => $formation->id, 'statut' => 'non_commencee']);

    $this->actingAs($apprenant)
        ->postJson(route('apprentissage.devoir.soumettre', $devoirM), ['contenu_texte' => 'Ma réponse'])
        ->assertSuccessful();
    $devoir = Devoir::first();

    // Rejet sans commentaire refusé.
    $this->actingAs($formateur)
        ->postJson(route('apprentissage.devoir.evaluer', $devoir), ['decision' => 'rejete'])
        ->assertStatus(422);

    // Rejet motivé accepté, module non validé.
    $this->actingAs($formateur)
        ->postJson(route('apprentissage.devoir.evaluer', $devoir), ['decision' => 'rejete', 'commentaire' => 'Incomplet'])
        ->assertSuccessful();

    expect(Progression::where('module_id', $devoirM->id)->where('statut', 'terminee')->exists())->toBeFalse();
});
