<?php

use App\Models\Chapitre;
use App\Models\Formation;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Attribution des formations.
 *
 * L'administrateur du site n'est pas un auteur de contenu : il dépanne, teste
 * et supervise. Quand il construit une formation à la place d'un formateur ou
 * d'un superviseur, elle doit être attribuée à cette personne — c'est son nom
 * qui figurera sur les certificats délivrés. Une formation rattachée à l'admin
 * est considérée comme non attribuée et ne peut pas être publiée.
 */

/** Ajoute un module pour que la formation soit publiable. */
function rendrePubliable(Formation $formation): void
{
    $chapitre = Chapitre::create([
        'formation_id' => $formation->id, 'titre' => 'C', 'position' => 0,
    ]);
    Module::create([
        'chapitre_id' => $chapitre->id, 'type' => 'pdf',
        'titre' => 'M', 'position' => 0, 'contenu' => 'm.pdf',
    ]);
}

it('impose à l’admin de désigner un responsable à la création', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('builder.formations.store'), [
            'titre' => 'Sans responsable',
            'type' => 'non_certifiante',
        ])
        ->assertSessionHasErrors('cree_par');

    $this->assertDatabaseCount('formations', 0);
});

it('attribue la formation au formateur choisi et trace l’opérateur', function () {
    $admin = User::factory()->admin()->create();
    $formateur = User::factory()->formateur()->create();

    $this->actingAs($admin)
        ->post(route('builder.formations.store'), [
            'titre' => 'Sécurité machine',
            'type' => 'non_certifiante',
            'cree_par' => $formateur->id,
        ])
        ->assertRedirect();

    $formation = Formation::firstOrFail();

    // Attribuée au formateur, mais on sait que l'admin a tenu le clavier.
    expect($formation->cree_par)->toBe($formateur->id)
        ->and($formation->saisi_par)->toBe($admin->id)
        ->and($formation->estAttribuee())->toBeTrue();
});

it('accepte aussi un superviseur comme responsable', function () {
    $admin = User::factory()->admin()->create();
    $superviseur = User::factory()->superviseur()->create();

    $this->actingAs($admin)
        ->post(route('builder.formations.store'), [
            'titre' => 'Qualité',
            'type' => 'non_certifiante',
            'cree_par' => $superviseur->id,
        ])
        ->assertRedirect();

    expect(Formation::firstOrFail()->cree_par)->toBe($superviseur->id);
});

it('refuse d’attribuer une formation à un apprenant ou à un admin', function () {
    $admin = User::factory()->admin()->create();
    $apprenant = User::factory()->create();
    $autreAdmin = User::factory()->admin()->create();

    foreach ([$apprenant->id, $autreAdmin->id] as $cible) {
        $this->actingAs($admin)
            ->post(route('builder.formations.store'), [
                'titre' => 'Interdite',
                'type' => 'non_certifiante',
                'cree_par' => $cible,
            ])
            ->assertSessionHasErrors('cree_par');
    }

    $this->assertDatabaseCount('formations', 0);
});

it('refuse de publier une formation non attribuée', function () {
    $admin = User::factory()->admin()->create();

    // Cas hérité : formation restée rattachée à l'admin.
    $formation = Formation::create([
        'titre' => 'Orpheline', 'type' => 'non_certifiante',
        'statut' => 'brouillon', 'cree_par' => $admin->id,
    ]);
    rendrePubliable($formation);

    $this->actingAs($admin)
        ->post(route('builder.formations.publier', $formation))
        ->assertStatus(422);

    expect($formation->fresh()->statut)->toBe('brouillon');
});

it('publie une fois la formation attribuée', function () {
    $admin = User::factory()->admin()->create();
    $formateur = User::factory()->formateur()->create();

    $formation = Formation::create([
        'titre' => 'Orpheline', 'type' => 'non_certifiante',
        'statut' => 'brouillon', 'cree_par' => $admin->id,
    ]);
    rendrePubliable($formation);

    // L'admin réattribue…
    $this->actingAs($admin)
        ->put(route('builder.formations.update', $formation), [
            'titre' => 'Orpheline',
            'type' => 'non_certifiante',
            'cree_par' => $formateur->id,
        ])
        ->assertRedirect();

    expect($formation->fresh()->cree_par)->toBe($formateur->id);

    // …puis la publication passe.
    $this->actingAs($admin)
        ->post(route('builder.formations.publier', $formation))
        ->assertRedirect();

    expect($formation->fresh()->statut)->toBe('publie');
});

it('empêche un formateur de réattribuer sa formation à quelqu’un d’autre', function () {
    $formateur = User::factory()->formateur()->create();
    $autre = User::factory()->formateur()->create();

    $formation = Formation::create([
        'titre' => 'À moi', 'type' => 'non_certifiante',
        'statut' => 'brouillon', 'cree_par' => $formateur->id,
    ]);

    $this->actingAs($formateur)
        ->put(route('builder.formations.update', $formation), [
            'titre' => 'À moi',
            'type' => 'non_certifiante',
            'cree_par' => $autre->id,
        ])
        ->assertRedirect();

    // Le champ est ignoré : la formation reste la sienne.
    expect($formation->fresh()->cree_par)->toBe($formateur->id);
});

it('laisse un formateur créer pour lui-même sans choisir de responsable', function () {
    $formateur = User::factory()->formateur()->create();

    $this->actingAs($formateur)
        ->post(route('builder.formations.store'), [
            'titre' => 'La mienne',
            'type' => 'non_certifiante',
        ])
        ->assertRedirect();

    $formation = Formation::firstOrFail();

    expect($formation->cree_par)->toBe($formateur->id)
        ->and($formation->saisi_par)->toBeNull();
});
