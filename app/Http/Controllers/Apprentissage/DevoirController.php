<?php

namespace App\Http\Controllers\Apprentissage;

use App\Http\Controllers\Controller;
use App\Http\Requests\Apprentissage\EvaluerDevoirRequest;
use App\Http\Requests\Apprentissage\SoumettreDevoirRequest;
use App\Http\Resources\DevoirResource;
use App\Models\Devoir;
use App\Models\Module;
use App\Services\MoteurCompletion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Devoirs : soumission par l'apprenant (statut en attente), puis approbation /
 * rejet par un formateur ou superviseur. Seule l'approbation débloque la
 * complétion du module (côté serveur).
 */
class DevoirController extends Controller
{
    /**
     * L'apprenant soumet un devoir (texte et/ou fichier).
     */
    public function soumettre(SoumettreDevoirRequest $request, Module $module, MoteurCompletion $completion): DevoirResource
    {
        if (! $module->estDevoir()) {
            throw new HttpException(422, "Ce module n'est pas un devoir.");
        }

        $completion->assurerAccessible($request->user(), $module);
        $progression = $completion->progression($request->user(), $module);
        $completion->marquerEnCours($progression);

        $fichier = $request->file('fichier');

        // Disque `local` (privé) : un rendu peut être de n'importe quel format,
        // il ne doit jamais être accessible directement par URL. La lecture
        // passe par `telecharger()`, qui vérifie les droits.
        $chemin = $fichier?->store('devoirs', 'local');

        $devoir = Devoir::create([
            'progression_id' => $progression->id,
            'contenu_texte' => $request->validated('contenu_texte'),
            'chemin_fichier' => $chemin,
            'nom_fichier' => $fichier?->getClientOriginalName(),
            'statut' => Devoir::STATUT_EN_ATTENTE,
            'soumis_le' => now(),
        ]);

        return new DevoirResource($devoir);
    }

    /**
     * Un formateur/superviseur approuve ou rejette (autorisation dans la Request).
     */
    public function evaluer(EvaluerDevoirRequest $request, Devoir $devoir, MoteurCompletion $completion): DevoirResource
    {
        if ($devoir->statut !== Devoir::STATUT_EN_ATTENTE) {
            throw new HttpException(422, 'Ce devoir a déjà été évalué.');
        }

        $decision = $request->validated('decision');
        $progression = $devoir->progression()->firstOrFail();

        DB::transaction(function () use ($devoir, $request, $decision, $progression, $completion) {
            $devoir->statut = $decision;
            $devoir->commentaire = $request->validated('commentaire');
            $devoir->evalue_par = $request->user()->id;
            $devoir->evalue_le = now();
            $devoir->save();

            if ($decision === Devoir::STATUT_APPROUVE) {
                $completion->terminerModule($progression);
            }
        });

        return new DevoirResource($devoir->load('evaluateur'));
    }

    /**
     * Télécharge le fichier rendu par l'apprenant.
     *
     * Les rendus vivent sur le disque privé et peuvent être de n'importe quel
     * format : ils ne sont jamais exposés par URL directe. Deux profils y ont
     * accès — l'auteur du rendu, et les personnes habilitées à le corriger
     * (formateur, superviseur, admin). Le fichier est renvoyé sous son nom
     * d'origine, le nom stocké étant un haché.
     */
    public function telecharger(Request $request, Devoir $devoir): StreamedResponse
    {
        if (! $devoir->chemin_fichier) {
            throw new HttpException(404, 'Aucun fichier joint à ce devoir.');
        }

        $user = $request->user();
        $progression = $devoir->progression()->with('inscription')->firstOrFail();

        $estAuteur = $progression->inscription->utilisateur_id === $user->id;
        $peutCorriger = $user->isFormateur() || $user->isSuperviseur() || $user->isAdmin();

        abort_unless($estAuteur || $peutCorriger, 403, 'Ce devoir ne vous est pas accessible.');

        abort_unless(Storage::disk('local')->exists($devoir->chemin_fichier), 404, 'Fichier introuvable.');

        return Storage::disk('local')->download(
            $devoir->chemin_fichier,
            $devoir->nomTelechargement()
        );
    }
}
