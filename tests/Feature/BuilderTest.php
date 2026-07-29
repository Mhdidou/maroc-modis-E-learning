<?php

namespace Tests\Feature;

use App\Models\Chapitre;
use App\Models\Formation;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Atelier formateur : construction, autorisation par auteur, publication.
 */
class BuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_formateur_construit_et_publie_une_formation(): void
    {
        $formateur = User::factory()->formateur()->create();

        $this->actingAs($formateur)
            ->post(route('builder.formations.store'), [
                'titre' => 'Couture avancée',
                'type' => 'certifiante',
                'validite_mois' => 12,
            ])->assertRedirect();

        $formation = Formation::firstWhere('titre', 'Couture avancée');
        $this->assertNotNull($formation);
        $this->assertSame($formateur->id, $formation->cree_par);
        $this->assertSame('brouillon', $formation->statut);

        // L'atelier s'affiche pour l'auteur (sans exception de lazy loading).
        $this->actingAs($formateur)->get(route('builder.show', $formation))->assertOk();

        $this->actingAs($formateur)
            ->post(route('builder.chapitres.store', $formation), ['titre' => 'Chapitre 1'])
            ->assertRedirect();
        $chapitre = Chapitre::firstWhere('formation_id', $formation->id);

        $this->actingAs($formateur)
            ->post(route('builder.modules.store', $chapitre), ['type' => 'quiz', 'titre' => 'Quiz 1'])
            ->assertRedirect();
        $module = Module::firstWhere('chapitre_id', $chapitre->id);

        $this->actingAs($formateur)
            ->post(route('builder.questions.store', $module), [
                'enonce' => 'Question ?',
                'bonne_reponse' => 'Oui',
                'mauvaises_reponses' => ['Non', 'Peut-être'],
            ])->assertRedirect();
        $this->assertSame(1, $module->questions()->count());

        // Publication (au moins un module présent).
        $this->actingAs($formateur)
            ->post(route('builder.formations.publier', $formation))
            ->assertRedirect();
        $this->assertSame('publie', $formation->refresh()->statut);
    }

    public function test_publication_refusee_sans_module(): void
    {
        $formateur = User::factory()->formateur()->create();
        $formation = Formation::create([
            'titre' => 'Vide', 'type' => 'non_certifiante',
            'statut' => 'brouillon', 'cree_par' => $formateur->id,
        ]);

        $this->actingAs($formateur)
            ->post(route('builder.formations.publier', $formation))
            ->assertStatus(422);
        $this->assertSame('brouillon', $formation->refresh()->statut);
    }

    public function test_un_formateur_ne_touche_pas_la_formation_d_un_autre(): void
    {
        $auteur = User::factory()->formateur()->create();
        $intrus = User::factory()->formateur()->create();
        $formation = Formation::create([
            'titre' => 'Privée', 'type' => 'non_certifiante',
            'statut' => 'brouillon', 'cree_par' => $auteur->id,
        ]);

        $this->actingAs($intrus)->get(route('builder.show', $formation))->assertForbidden();
        $this->actingAs($intrus)
            ->post(route('builder.chapitres.store', $formation), ['titre' => 'X'])
            ->assertForbidden();
    }

    public function test_un_apprenant_est_refuse(): void
    {
        $apprenant = User::factory()->create();

        $this->actingAs($apprenant)->get(route('builder.index'))->assertForbidden();
    }

    /**
     * Le superviseur entre dans l'atelier — une formation peut lui être
     * attribuée — mais reste borné à celles dont il est responsable.
     */
    public function test_un_superviseur_accede_a_l_atelier_mais_pas_aux_formations_d_autrui(): void
    {
        $superviseur = User::factory()->superviseur()->create();
        $formateur = User::factory()->formateur()->create();

        $this->actingAs($superviseur)->get(route('builder.index'))->assertOk();

        $sienne = Formation::create([
            'titre' => 'À moi', 'type' => 'non_certifiante',
            'statut' => 'brouillon', 'cree_par' => $superviseur->id,
        ]);
        $autre = Formation::create([
            'titre' => "D'un autre", 'type' => 'non_certifiante',
            'statut' => 'brouillon', 'cree_par' => $formateur->id,
        ]);

        $this->actingAs($superviseur)->get(route('builder.show', $sienne))->assertOk();
        $this->actingAs($superviseur)->get(route('builder.show', $autre))->assertForbidden();
    }

    public function test_reordonnancement_des_chapitres(): void
    {
        $formateur = User::factory()->formateur()->create();
        $formation = Formation::create([
            'titre' => 'Ordre', 'type' => 'non_certifiante',
            'statut' => 'brouillon', 'cree_par' => $formateur->id,
        ]);
        $c1 = Chapitre::create(['formation_id' => $formation->id, 'titre' => 'A', 'position' => 0]);
        $c2 = Chapitre::create(['formation_id' => $formation->id, 'titre' => 'B', 'position' => 1]);

        $this->actingAs($formateur)
            ->post(route('builder.chapitres.reordonner', $formation), ['ordre' => [$c2->id, $c1->id]])
            ->assertRedirect();

        $this->assertSame(0, $c2->refresh()->position);
        $this->assertSame(1, $c1->refresh()->position);
    }
}
