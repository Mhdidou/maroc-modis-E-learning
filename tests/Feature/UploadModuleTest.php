<?php

use App\Models\Chapitre;
use App\Models\Formation;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function moduleVideo(User $auteur): Module
{
    $formation = Formation::create(['titre' => 'F', 'type' => 'non_certifiante', 'statut' => 'brouillon', 'cree_par' => $auteur->id]);
    $chapitre = Chapitre::create(['formation_id' => $formation->id, 'titre' => 'C', 'position' => 0]);

    return Module::create(['chapitre_id' => $chapitre->id, 'type' => 'video', 'titre' => 'V', 'position' => 0]);
}

it('téléverse une vidéo et met à jour le contenu', function () {
    Storage::fake('public');
    $formateur = User::factory()->formateur()->create();
    $module = moduleVideo($formateur);

    $this->actingAs($formateur)
        ->post(route('builder.modules.fichier', $module), [
            'fichier' => UploadedFile::fake()->create('cours.mp4', 500, 'video/mp4'),
        ])
        ->assertRedirect();

    $module->refresh();
    expect($module->contenu)->toStartWith('/storage/modules/video/');
    Storage::disk('public')->assertExists(str_replace('/storage/', '', $module->contenu));
});

/**
 * Régression : une vidéo de 12,4 Mo était refusée non par cette règle, mais par
 * `post_max_size` de PHP (8 Mo par défaut sous WAMP), bien avant que Laravel ne
 * voie la requête. La règle applicative doit rester large — si ce test passe
 * mais que l'import échoue en vrai, le blocage est dans php.ini, et le serveur
 * web doit être redémarré après l'avoir corrigé.
 */
it('accepte une vidéo de 12,4 Mo', function () {
    Storage::fake('public');
    $formateur = User::factory()->formateur()->create();
    $module = moduleVideo($formateur);

    $this->actingAs($formateur)
        ->post(route('builder.modules.fichier', $module), [
            'fichier' => UploadedFile::fake()->create('atelier.mp4', 12698, 'video/mp4'),
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($module->refresh()->contenu)->toStartWith('/storage/modules/video/');
});

it('refuse un format non pris en charge', function () {
    Storage::fake('public');
    $formateur = User::factory()->formateur()->create();
    $module = moduleVideo($formateur);

    $this->actingAs($formateur)
        ->post(route('builder.modules.fichier', $module), [
            'fichier' => UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'),
        ])
        ->assertSessionHasErrors('fichier');

    expect($module->refresh()->contenu)->toBeNull();
});

it('remplace l’ancien fichier importé', function () {
    Storage::fake('public');
    $formateur = User::factory()->formateur()->create();
    $module = moduleVideo($formateur);

    $this->actingAs($formateur)->post(route('builder.modules.fichier', $module), [
        'fichier' => UploadedFile::fake()->create('a.mp4', 100, 'video/mp4'),
    ]);
    $ancien = str_replace('/storage/', '', $module->refresh()->contenu);

    $this->actingAs($formateur)->post(route('builder.modules.fichier', $module), [
        'fichier' => UploadedFile::fake()->create('b.mp4', 100, 'video/mp4'),
    ]);

    Storage::disk('public')->assertMissing($ancien);
    Storage::disk('public')->assertExists(str_replace('/storage/', '', $module->refresh()->contenu));
});

it('interdit l’import sur la formation d’un autre formateur', function () {
    Storage::fake('public');
    $auteur = User::factory()->formateur()->create();
    $intrus = User::factory()->formateur()->create();
    $module = moduleVideo($auteur);

    $this->actingAs($intrus)
        ->post(route('builder.modules.fichier', $module), [
            'fichier' => UploadedFile::fake()->create('x.mp4', 100, 'video/mp4'),
        ])
        ->assertForbidden();
});
