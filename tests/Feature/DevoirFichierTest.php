<?php

use App\Models\Chapitre;
use App\Models\Devoir;
use App\Models\Formation;
use App\Models\Inscription;
use App\Models\Module;
use App\Models\Progression;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

/**
 * Devoirs : pièce jointe d'explication côté formateur, rendu de fichier côté
 * apprenant. Tous les formats de rendu sont acceptés (photo d'une pièce cousue,
 * scan, vidéo d'un geste technique…) ; le fichier reste sur le disque privé et
 * n'est lisible que par son auteur ou par un correcteur.
 */

/** Un module devoir accessible, avec l'apprenant inscrit. */
function contexteDevoir(): array
{
    $formateur = User::factory()->formateur()->create();
    $apprenant = User::factory()->create();

    $formation = Formation::create([
        'titre' => 'Couture', 'type' => 'non_certifiante', 'statut' => 'publie',
        'cree_par' => $formateur->id,
    ]);
    $chapitre = Chapitre::create([
        'formation_id' => $formation->id, 'titre' => 'C', 'position' => 0,
    ]);
    $devoirModule = Module::create([
        'chapitre_id' => $chapitre->id, 'type' => 'devoir', 'titre' => 'Rendu',
        'position' => 0, 'consignes' => 'Cousez une couture droite.',
    ]);

    $inscription = Inscription::create([
        'utilisateur_id' => $apprenant->id, 'formation_id' => $formation->id,
        'statut' => 'en_cours',
    ]);

    return compact('formateur', 'apprenant', 'formation', 'devoirModule', 'inscription');
}

/* ---------------------------------------------------------------------- */
/*  Pièce jointe d'explication (formateur) */
/* ---------------------------------------------------------------------- */

it('accepte une vidéo d’explication sur un module devoir', function () {
    Storage::fake('public');
    ['formateur' => $formateur, 'devoirModule' => $module] = contexteDevoir();

    $this->actingAs($formateur)
        ->post(route('builder.modules.fichier', $module), [
            'fichier' => UploadedFile::fake()->create('enonce.mp4', 2048, 'video/mp4'),
        ])
        ->assertRedirect();

    $module->refresh();

    expect($module->contenu)->not->toBeNull()
        ->and($module->typePieceJointe())->toBe('video');
});

it('accepte aussi un PDF d’explication sur un module devoir', function () {
    Storage::fake('public');
    ['formateur' => $formateur, 'devoirModule' => $module] = contexteDevoir();

    $this->actingAs($formateur)
        ->post(route('builder.modules.fichier', $module), [
            'fichier' => UploadedFile::fake()->create('sujet.pdf', 500, 'application/pdf'),
        ])
        ->assertRedirect();

    expect($module->refresh()->typePieceJointe())->toBe('pdf');
});

it('refuse un format non pris en charge comme pièce jointe', function () {
    Storage::fake('public');
    ['formateur' => $formateur, 'devoirModule' => $module] = contexteDevoir();

    $this->actingAs($formateur)
        ->post(route('builder.modules.fichier', $module), [
            'fichier' => UploadedFile::fake()->create('feuille.xlsx', 10),
        ])
        ->assertSessionHasErrors('fichier');

    expect($module->refresh()->contenu)->toBeNull();
});

/* ---------------------------------------------------------------------- */
/*  Rendu de l'apprenant : tous formats */
/* ---------------------------------------------------------------------- */

it('accepte n’importe quel format en rendu de devoir', function () {
    Storage::fake('local');
    ['apprenant' => $apprenant, 'devoirModule' => $module] = contexteDevoir();

    // Un tableur : refusé par l'ancienne liste blanche pdf/doc/jpg/png.
    $this->actingAs($apprenant)
        ->postJson(route('apprentissage.devoir.soumettre', $module), [
            'fichier' => UploadedFile::fake()->create('releve.xlsx', 120),
        ])
        ->assertSuccessful();

    $devoir = Devoir::firstOrFail();

    // Le nom d'origine est conservé alors que le stockage est haché.
    expect($devoir->nom_fichier)->toBe('releve.xlsx')
        ->and($devoir->chemin_fichier)->not->toContain('releve.xlsx');

    Storage::disk('local')->assertExists($devoir->chemin_fichier);
});

it('rejette un rendu dépassant la taille maximale', function () {
    Storage::fake('local');
    ['apprenant' => $apprenant, 'devoirModule' => $module] = contexteDevoir();

    $this->actingAs($apprenant)
        ->postJson(route('apprentissage.devoir.soumettre', $module), [
            'fichier' => UploadedFile::fake()->create('enorme.zip', 102401),
        ])
        ->assertStatus(422);

    $this->assertDatabaseCount('devoirs', 0);
});

/* ---------------------------------------------------------------------- */
/*  Téléchargement du rendu */
/* ---------------------------------------------------------------------- */

it('laisse l’auteur et le correcteur télécharger le rendu, mais pas un tiers', function () {
    Storage::fake('local');
    ['apprenant' => $apprenant, 'formateur' => $formateur, 'devoirModule' => $module] = contexteDevoir();

    $this->actingAs($apprenant)
        ->postJson(route('apprentissage.devoir.soumettre', $module), [
            'fichier' => UploadedFile::fake()->create('photo.jpg', 80),
        ])
        ->assertSuccessful();

    $devoir = Devoir::firstOrFail();
    $url = route('apprentissage.devoir.fichier', $devoir);

    // L'auteur récupère son fichier sous son nom d'origine.
    $this->actingAs($apprenant)->get($url)
        ->assertOk()
        ->assertDownload('photo.jpg');

    // Le correcteur aussi.
    $this->actingAs($formateur)->get($url)->assertOk();

    // Un autre apprenant, non.
    $tiers = User::factory()->create();
    $this->actingAs($tiers)->get($url)->assertForbidden();
});

it('renvoie 404 quand le devoir n’a pas de fichier', function () {
    ['apprenant' => $apprenant, 'devoirModule' => $module] = contexteDevoir();

    $progression = Progression::create([
        'inscription_id' => Inscription::firstOrFail()->id,
        'module_id' => $module->id,
        'statut' => 'en_cours',
    ]);
    $devoir = Devoir::create([
        'progression_id' => $progression->id,
        'contenu_texte' => 'Réponse en texte seulement.',
        'statut' => Devoir::STATUT_EN_ATTENTE,
        'soumis_le' => now(),
    ]);

    $this->actingAs($apprenant)
        ->get(route('apprentissage.devoir.fichier', $devoir))
        ->assertNotFound();
});
