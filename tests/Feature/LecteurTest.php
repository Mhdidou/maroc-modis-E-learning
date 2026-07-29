<?php

namespace Tests\Feature;

use App\Models\Chapitre;
use App\Models\CheckpointVideo;
use App\Models\Formation;
use App\Models\Inscription;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Lecteur apprenant : sécurité du payload (aucune réponse divulguée) et
 * gating séquentiel décidé serveur.
 */
class LecteurTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(): array
    {
        $formateur = User::factory()->formateur()->create();
        $apprenant = User::factory()->create();
        $formation = Formation::create([
            'titre' => 'F', 'type' => 'non_certifiante',
            'statut' => 'publie', 'cree_par' => $formateur->id,
        ]);
        $chapitre = Chapitre::create(['formation_id' => $formation->id, 'titre' => 'C', 'position' => 0]);
        $pdf = Module::create(['chapitre_id' => $chapitre->id, 'type' => 'pdf', 'titre' => 'Doc', 'position' => 0, 'contenu' => 'x.pdf']);
        $video = Module::create(['chapitre_id' => $chapitre->id, 'type' => 'video', 'titre' => 'Vid', 'position' => 1, 'contenu' => 'x.mp4']);
        CheckpointVideo::create([
            'module_id' => $video->id, 'position_secondes' => 10,
            'enonce' => 'Q', 'bonne_reponse' => 'BON',
            'mauvaises_reponses' => ['X', 'Y'], 'explication' => 'SECRET',
        ]);
        $inscription = Inscription::create([
            'utilisateur_id' => $apprenant->id, 'formation_id' => $formation->id, 'statut' => 'non_commencee',
        ]);

        return compact('apprenant', 'formation', 'pdf', 'video', 'inscription');
    }

    public function test_payload_ne_divulgue_pas_les_reponses_et_gate_les_modules(): void
    {
        ['apprenant' => $apprenant, 'formation' => $formation] = $this->fixture();

        $response = $this->actingAs($apprenant)->get(route('apprentissage.formation', $formation));
        $response->assertOk();

        $page = json_decode(json_encode($response->viewData('page')), true);
        $modules = $page['props']['formation']['chapitres'][0]['modules'];

        // Gating : 1er accessible, 2e verrouillé tant que le 1er n'est pas fini.
        $this->assertTrue($modules[0]['accessible']);
        $this->assertFalse($modules[1]['accessible']);

        // Le checkpoint expose des options mais jamais la réponse ni l'explication.
        $cp = $modules[1]['checkpoints'][0];
        $this->assertCount(3, $cp['options']);
        $this->assertArrayNotHasKey('bonne_reponse', $cp);
        $this->assertArrayNotHasKey('explication', $cp);
        $this->assertStringNotContainsString('SECRET', json_encode($modules[1]));
    }

    public function test_impossible_de_terminer_un_module_hors_ordre(): void
    {
        ['apprenant' => $apprenant, 'pdf' => $pdf, 'video' => $video] = $this->fixture();

        // La vidéo est verrouillée tant que le PDF n'est pas terminé.
        $this->actingAs($apprenant)
            ->post(route('apprentissage.modules.terminer', $video))
            ->assertStatus(422);

        // On termine le PDF (module accessible).
        $this->actingAs($apprenant)
            ->post(route('apprentissage.modules.terminer', $pdf))
            ->assertSuccessful();

        // Désormais la vidéo est accessible (mais bloquée par son checkpoint).
        $this->actingAs($apprenant)
            ->post(route('apprentissage.modules.terminer', $video))
            ->assertStatus(422); // checkpoint non résolu, pas l'ordre
    }

    public function test_non_inscrit_refuse(): void
    {
        ['formation' => $formation] = $this->fixture();
        $autre = User::factory()->create();

        $this->actingAs($autre)
            ->get(route('apprentissage.formation', $formation))
            ->assertStatus(403);
    }
}
