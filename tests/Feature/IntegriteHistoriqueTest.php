<?php

use App\Models\Certificat;
use App\Models\Formation;
use App\Models\Inscription;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Intégrité de l'historique de formation.
 *
 * Règle d'or : un dossier de formation (inscription, progression, certificat)
 * est une pièce d'audit — client donneur d'ordre, ISO 9001, habilitations
 * sécurité. Rien dans l'application ne doit pouvoir l'effacer. Le départ d'un
 * employé se traite par désactivation, le retrait d'une formation du catalogue
 * par dépublication.
 */

/** Un apprenant certifié : inscription terminée + certificat délivré. */
function apprenantCertifie(): array
{
    $formateur = User::factory()->formateur()->create(['nom_complet' => 'Youssef Formateur']);
    $apprenant = User::factory()->create([
        'nom_complet' => 'Amina Benali',
        'email' => 'amina@usine.ma',
        'domaine' => 'Couture',
    ]);

    $formation = Formation::create([
        'titre' => 'Sécurité machine', 'type' => 'certifiante', 'statut' => 'publie',
        'validite_mois' => 24, 'cree_par' => $formateur->id,
    ]);

    $inscription = Inscription::create([
        'utilisateur_id' => $apprenant->id, 'formation_id' => $formation->id, 'statut' => 'terminee',
    ]);

    $certificat = Certificat::create([
        'utilisateur_id' => $apprenant->id,
        'formation_id' => $formation->id,
        'numero_unique' => 'MM-2026-000042',
        'delivre_le' => now(),
        'expire_le' => now()->addMonths(24),
    ]);

    return compact('formateur', 'apprenant', 'formation', 'inscription', 'certificat');
}

/* ---------------------------------------------------------------------- */
/*  Désactivation d'un employé */
/* ---------------------------------------------------------------------- */

it('conserve inscription et certificat quand un employé est désactivé', function () {
    ['apprenant' => $apprenant, 'inscription' => $inscription, 'certificat' => $certificat] = apprenantCertifie();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->delete(route('utilisateurs.destroy', $apprenant->id))
        ->assertRedirect(route('utilisateurs.index'));

    // Le compte est désactivé, pas supprimé.
    expect(User::find($apprenant->id))->toBeNull();
    expect(User::withTrashed()->find($apprenant->id)->estActif())->toBeFalse();

    // L'historique de formation est intact.
    $this->assertDatabaseHas('inscriptions', ['id' => $inscription->id]);
    $this->assertDatabaseHas('certificats', ['id' => $certificat->id]);
});

it('retire immédiatement l’accès à un employé désactivé', function () {
    ['apprenant' => $apprenant] = apprenantCertifie();

    $apprenant->delete();

    // Le global scope de SoftDeletes exclut le compte de l'authentification.
    $this->post('/login', ['email' => 'amina@usine.ma', 'password' => 'password'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('rend l’accès et l’historique d’origine à la réactivation', function () {
    ['apprenant' => $apprenant, 'certificat' => $certificat] = apprenantCertifie();
    $admin = User::factory()->admin()->create();

    $apprenant->delete();

    $this->actingAs($admin)
        ->post(route('utilisateurs.restore', $apprenant->id))
        ->assertRedirect(route('utilisateurs.index'));

    expect(User::find($apprenant->id))->not->toBeNull();
    expect(User::find($apprenant->id)->certificats()->count())->toBe(1)
        ->and(Certificat::find($certificat->id))->not->toBeNull();
});

it('interdit à un superviseur de désactiver un autre superviseur', function () {
    $superviseur = User::factory()->superviseur()->create();
    $collegue = User::factory()->superviseur()->create();

    $this->actingAs($superviseur)
        ->delete(route('utilisateurs.destroy', $collegue->id))
        ->assertForbidden();

    expect(User::find($collegue->id))->not->toBeNull();
});

it('interdit de se désactiver soi-même', function () {
    $admin = User::factory()->admin()->create();
    $superviseur = User::factory()->superviseur()->create();

    // L'admin gère les superviseurs mais pas les admins : on vérifie le
    // verrou « soi-même » sur un superviseur, rôle qu'il peut gérer.
    $this->actingAs($superviseur)
        ->delete(route('utilisateurs.destroy', $superviseur->id))
        ->assertForbidden();

    expect(User::find($superviseur->id))->not->toBeNull();
    expect($admin->fresh())->not->toBeNull();
});

/* ---------------------------------------------------------------------- */
/*  Le certificat reste une pièce d'audit lisible */
/* ---------------------------------------------------------------------- */

it('garde un certificat vérifiable publiquement après le départ de l’employé', function () {
    ['apprenant' => $apprenant, 'certificat' => $certificat] = apprenantCertifie();

    $apprenant->delete();

    // Un auditeur scanne le QR code : le titulaire doit toujours s'afficher.
    $this->get(route('certificats.verifier', $certificat->numero_unique))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('certificat.apprenant', 'Amina Benali')
            ->where('certificat.domaine', 'Couture')
            ->where('certificat.formation', 'Sécurité machine')
        );
});

/* ---------------------------------------------------------------------- */
/*  Verrous en base : la suppression physique doit échouer */
/* ---------------------------------------------------------------------- */

it('refuse en base la suppression physique du titulaire d’un certificat', function () {
    ['apprenant' => $apprenant] = apprenantCertifie();

    // Dernier rempart : même en contournant le soft delete, les FK RESTRICT
    // empêchent d'effacer un dossier de formation.
    expect(fn () => $apprenant->forceDelete())->toThrow(QueryException::class);

    $this->assertDatabaseCount('certificats', 1);
});

it('refuse en base la suppression physique d’une formation certifiée', function () {
    ['formation' => $formation] = apprenantCertifie();

    expect(fn () => Formation::withoutEvents(fn () => $formation->forceDelete()))
        ->toThrow(QueryException::class);

    $this->assertDatabaseCount('certificats', 1);
});

/* ---------------------------------------------------------------------- */
/*  Suppression d'une formation depuis le builder */
/* ---------------------------------------------------------------------- */

it('refuse de supprimer une formation déjà suivie ou certifiée', function () {
    ['formateur' => $formateur, 'formation' => $formation, 'certificat' => $certificat] = apprenantCertifie();

    $this->actingAs($formateur)
        ->from(route('builder.show', $formation->id))
        ->delete(route('builder.formations.destroy', $formation->id))
        ->assertRedirect(route('builder.show', $formation->id))
        ->assertSessionHasErrors('suppression');

    $this->assertDatabaseHas('formations', ['id' => $formation->id]);
    $this->assertDatabaseHas('certificats', ['id' => $certificat->id]);
});

it('autorise la suppression d’une formation jamais suivie', function () {
    $formateur = User::factory()->formateur()->create();

    $formation = Formation::create([
        'titre' => 'Brouillon', 'type' => 'non_certifiante', 'statut' => 'brouillon',
        'cree_par' => $formateur->id,
    ]);

    $this->actingAs($formateur)
        ->delete(route('builder.formations.destroy', $formation->id))
        ->assertRedirect(route('builder.index'));

    $this->assertDatabaseMissing('formations', ['id' => $formation->id]);
});
