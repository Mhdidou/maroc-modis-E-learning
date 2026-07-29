<?php

namespace App\Http\Controllers\Apprentissage;

use App\Http\Controllers\Controller;
use App\Models\CheckpointResolu;
use App\Models\Devoir;
use App\Models\Formation;
use App\Models\Inscription;
use App\Models\Module;
use App\Models\Progression;
use App\Models\TentativeQuiz;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Lecteur apprenant : sert l'arbre d'une formation avec l'état de progression
 * lu depuis MySQL (jamais du client). Les données sensibles (bonne réponse,
 * explication d'un checkpoint) ne sont JAMAIS incluses ici — seulement des
 * options mélangées. La progression, l'accessibilité séquentielle des modules
 * et les compteurs de tentatives sont décidés serveur.
 */
class LecteurController extends Controller
{
    public function formation(Request $request, Formation $formation): Response
    {
        $user = $request->user();

        $inscription = Inscription::where('utilisateur_id', $user->id)
            ->where('formation_id', $formation->id)
            ->first();

        if (! $inscription) {
            throw new HttpException(403, "Vous n'êtes pas inscrit à cette formation.");
        }

        $formation->load([
            'chapitres' => fn ($q) => $q->orderBy('position'),
            'chapitres.modules' => fn ($q) => $q->orderBy('position'),
            'chapitres.modules.questions:id,module_id',
            'chapitres.modules.checkpointsVideo' => fn ($q) => $q->orderBy('position_secondes'),
        ]);

        // État agrégé, indexé par module, en quelques requêtes bornées.
        $progressions = Progression::where('inscription_id', $inscription->id)
            ->get()
            ->keyBy('module_id');

        $progIds = $progressions->pluck('id');

        $resolusParModule = CheckpointResolu::whereIn('progression_id', $progIds)
            ->get()
            ->groupBy('progression_id');

        $tentativesParModule = TentativeQuiz::where('inscription_id', $inscription->id)
            ->get()
            ->groupBy('module_id');

        $devoirsParProg = Devoir::whereIn('progression_id', $progIds)
            ->orderBy('id')
            ->get()
            ->groupBy('progression_id');

        // Accessibilité séquentielle : un module s'ouvre quand tous les
        // précédents (ordre global chapitre→module) sont terminés.
        $tousPrecedentsTermines = true;

        $chapitres = $formation->chapitres->map(function ($chapitre) use (
            $progressions, $resolusParModule, $tentativesParModule, $devoirsParProg, &$tousPrecedentsTermines
        ) {
            $modules = $chapitre->modules->map(function (Module $module) use (
                $progressions, $resolusParModule, $tentativesParModule, $devoirsParProg, &$tousPrecedentsTermines
            ) {
                $prog = $progressions->get($module->id);
                $termine = $prog && $prog->statut === 'terminee';
                $accessible = $tousPrecedentsTermines;

                $base = [
                    'id' => $module->id,
                    'type' => $module->type,
                    'titre' => $module->titre,
                    'position' => $module->position,
                    'statut' => $prog->statut ?? 'non_commencee',
                    'score' => $prog->score ?? null,
                    'accessible' => $accessible,
                    'termine' => $termine,
                ];

                $extras = match ($module->type) {
                    Module::TYPE_VIDEO => [
                        'contenu' => $module->contenu,
                        'checkpoints' => $module->checkpointsVideo->map(fn ($cp) => [
                            'id' => $cp->id,
                            'position_secondes' => $cp->position_secondes,
                            'enonce' => $cp->enonce,
                            // Options mélangées, sans indiquer la bonne réponse.
                            'options' => collect($cp->mauvaises_reponses)
                                ->push($cp->bonne_reponse)
                                ->shuffle()
                                ->values(),
                        ]),
                        'resolus' => $prog
                            ? ($resolusParModule->get($prog->id)?->pluck('checkpoint_id')->values() ?? [])
                            : [],
                    ],
                    Module::TYPE_PDF => [
                        'contenu' => $module->contenu,
                    ],
                    Module::TYPE_QUIZ => $this->extrasQuiz($module, $tentativesParModule->get($module->id)),
                    Module::TYPE_DEVOIR => $this->extrasDevoir($module, $prog ? $devoirsParProg->get($prog->id) : null),
                    default => [],
                };

                // Met à jour l'accumulateur APRÈS avoir figé l'accessibilité.
                $tousPrecedentsTermines = $tousPrecedentsTermines && $termine;

                return $base + $extras;
            });

            return [
                'id' => $chapitre->id,
                'titre' => $chapitre->titre,
                'modules' => $modules,
            ];
        });

        return Inertia::render('Apprentissage/Formation', [
            'formation' => [
                'id' => $formation->id,
                'titre' => $formation->titre,
                'type' => $formation->type,
                'statut' => $inscription->statut,
                'chapitres' => $chapitres,
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function extrasQuiz(Module $module, $tentatives): array
    {
        $tentatives ??= collect();
        $ouverte = $tentatives->firstWhere('termine_le', null);

        return [
            'seuil_reussite' => $module->seuil_reussite,
            'duree_minutes' => $module->duree_minutes,
            'nb_questions_tirees' => $module->nb_questions_tirees,
            'banque_total' => $module->questions->count(),
            'tentatives_utilisees' => $tentatives->count(),
            'tentatives_max' => TentativeQuiz::MAX_TENTATIVES,
            'reussi' => $tentatives->contains('reussi', true),
            'tentative_ouverte_id' => $ouverte?->id,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function extrasDevoir(Module $module, $devoirs): array
    {
        $dernier = $devoirs?->last();

        return [
            'consignes' => $module->consignes,
            // Pièce jointe facultative d'explication (énoncé filmé ou sujet PDF).
            // `piece_jointe_type` dit au front quel lecteur utiliser.
            'piece_jointe' => $module->contenu,
            'piece_jointe_type' => $module->typePieceJointe(),
            'devoir' => $dernier ? [
                'id' => $dernier->id,
                'statut' => $dernier->statut,
                'commentaire' => $dernier->commentaire,
                'a_fichier' => (bool) $dernier->chemin_fichier,
                'nom_fichier' => $dernier->nom_fichier,
            ] : null,
        ];
    }
}
