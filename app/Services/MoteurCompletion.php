<?php

namespace App\Services;

use App\Jobs\GenererCertificat;
use App\Models\Inscription;
use App\Models\Module;
use App\Models\Progression;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Décisions de complétion 100 % serveur (règle d'or : le client propose, le
 * serveur dispose). Résout la progression d'un apprenant, vérifie les
 * préconditions de complétion d'un module et fait remonter la cascade
 * module → chapitre → formation, jusqu'à la génération du certificat.
 */
class MoteurCompletion
{
    /**
     * Retourne (en la créant au besoin) la progression de l'apprenant sur ce
     * module, après vérification qu'il est bien inscrit à la formation.
     */
    public function progression(User $user, Module $module): Progression
    {
        $formationId = $module->chapitre()->value('formation_id');

        $inscription = Inscription::where('utilisateur_id', $user->id)
            ->where('formation_id', $formationId)
            ->first();

        if (! $inscription) {
            throw new HttpException(403, "Vous n'êtes pas inscrit à cette formation.");
        }

        return Progression::firstOrCreate(
            ['inscription_id' => $inscription->id, 'module_id' => $module->id],
            ['statut' => 'en_cours'],
        );
    }

    /**
     * Garantit qu'un module est accessible : tous les modules qui le précèdent
     * (ordre global chapitre → module) doivent être terminés. Empêche de sauter
     * l'ordre côté serveur (le client ne fait que proposer).
     */
    public function assurerAccessible(User $user, Module $module): void
    {
        $formationId = $module->chapitre()->value('formation_id');

        $inscription = Inscription::where('utilisateur_id', $user->id)
            ->where('formation_id', $formationId)
            ->first();

        if (! $inscription) {
            throw new HttpException(403, "Vous n'êtes pas inscrit à cette formation.");
        }

        $ordre = Module::query()
            ->join('chapitres', 'modules.chapitre_id', '=', 'chapitres.id')
            ->where('chapitres.formation_id', $formationId)
            ->orderBy('chapitres.position')
            ->orderBy('modules.position')
            ->pluck('modules.id')
            ->all();

        $index = array_search($module->id, $ordre, true);
        if ($index === false || $index === 0) {
            return; // premier module (ou introuvable) : toujours accessible
        }

        $precedents = array_slice($ordre, 0, $index);
        $termines = Progression::where('inscription_id', $inscription->id)
            ->whereIn('module_id', $precedents)
            ->where('statut', 'terminee')
            ->count();

        if ($termines < count($precedents)) {
            throw new HttpException(422, 'Terminez d’abord le module précédent.');
        }
    }

    /**
     * Fait passer une progression « non commencée » à « en cours ».
     */
    public function marquerEnCours(Progression $progression): void
    {
        if ($progression->statut === 'non_commencee') {
            $progression->statut = 'en_cours';
            $progression->save();
        }
    }

    /**
     * Le module peut-il être marqué terminé au vu de l'état serveur ?
     *  - vidéo : tous les checkpoints doivent être résolus ;
     *  - pdf   : aucune précondition ;
     *  - quiz  : passe par la réussite d'une tentative (jamais ici) ;
     *  - devoir: passe par l'approbation (jamais ici).
     */
    public function peutTerminer(Module $module, Progression $progression): bool
    {
        return match ($module->type) {
            Module::TYPE_VIDEO => $progression->checkpointsResolus()->count()
                >= $module->checkpointsVideo()->count(),
            Module::TYPE_PDF => true,
            default => false,
        };
    }

    /**
     * Marque un module terminé puis déclenche la cascade. Idempotent.
     */
    public function terminerModule(Progression $progression, ?int $score = null): Progression
    {
        return DB::transaction(function () use ($progression, $score) {
            if ($progression->statut !== 'terminee') {
                $progression->statut = 'terminee';
                $progression->termine_le = now();
            }
            if ($score !== null) {
                $progression->score = $score;
            }
            $progression->save();

            $this->cascade($progression->inscription_id);

            return $progression;
        });
    }

    /**
     * Cascade de complétion au niveau de la formation : si tous les modules de
     * la formation sont terminés, l'inscription passe « terminée » et, pour une
     * formation certifiante, la génération du certificat est mise en file.
     */
    public function cascade(int $inscriptionId): void
    {
        $inscription = Inscription::with('formation')->findOrFail($inscriptionId);

        $modulesTotal = Module::whereHas(
            'chapitre',
            fn ($q) => $q->where('formation_id', $inscription->formation_id),
        )->count();

        $termines = Progression::where('inscription_id', $inscriptionId)
            ->where('statut', 'terminee')
            ->count();

        if ($modulesTotal > 0 && $termines >= $modulesTotal) {
            if ($inscription->statut !== 'terminee') {
                $inscription->statut = 'terminee';
                $inscription->termine_le = now();
                $inscription->save();

                if ($inscription->formation->isCertifiante()) {
                    GenererCertificat::dispatch($inscription->id);
                }
            }

            return;
        }

        if ($inscription->statut === 'non_commencee') {
            $inscription->statut = 'en_cours';
            $inscription->save();
        }
    }
}
