<?php

namespace App\Services;

use App\Models\CheckpointResolu;
use App\Models\Module;
use App\Models\Progression;
use App\Models\Question;
use App\Models\ReinitialisationChapitre;
use App\Models\TentativeQuiz;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Moteur du quiz noté (évaluatif). Timer, tirage aléatoire, comptage des
 * tentatives, correction et réinitialisation de chapitre au 3e échec : tout est
 * décidé et stocké côté serveur. Le client ne transmet que des intentions.
 */
class MoteurQuiz
{
    public function __construct(private MoteurCompletion $completion) {}

    /**
     * Démarre (ou reprend) une tentative. Fige le tirage des questions et
     * l'horodatage de départ. Renvoie la tentative ouverte.
     */
    public function demarrer(User $user, Module $module): TentativeQuiz
    {
        if (! $module->estQuiz()) {
            throw new HttpException(422, "Ce module n'est pas un quiz noté.");
        }

        $this->completion->assurerAccessible($user, $module);
        $progression = $this->completion->progression($user, $module);
        $this->completion->marquerEnCours($progression);

        // Reprise d'une tentative déjà ouverte (rechargement de page).
        $ouverte = TentativeQuiz::where('inscription_id', $progression->inscription_id)
            ->where('module_id', $module->id)
            ->whereNull('termine_le')
            ->first();

        if ($ouverte) {
            return $ouverte;
        }

        $numero = TentativeQuiz::where('inscription_id', $progression->inscription_id)
            ->where('module_id', $module->id)
            ->count() + 1;

        if ($numero > TentativeQuiz::MAX_TENTATIVES) {
            throw new HttpException(422, 'Nombre de tentatives épuisé.');
        }

        $x = $module->nb_questions_tirees ?: 5;
        $ids = Question::where('module_id', $module->id)
            ->inRandomOrder()
            ->limit($x)
            ->pluck('id')
            ->all();

        return TentativeQuiz::create([
            'inscription_id' => $progression->inscription_id,
            'module_id' => $module->id,
            'numero' => $numero,
            'questions_tirees' => $ids,
            'demarre_le' => now(),
        ]);
    }

    /**
     * Questions de la tentative, mélangées, SANS la bonne réponse (jamais avant
     * correction). Les options combinent bonne + mauvaises réponses.
     *
     * @return array<int, array{id:int, enonce:string, options:array<int,string>}>
     */
    public function questionsPourAffichage(TentativeQuiz $tentative): array
    {
        $questions = Question::whereIn('id', $tentative->questions_tirees)->get()
            ->keyBy('id');

        // Respecte l'ordre figé du tirage.
        return collect($tentative->questions_tirees)
            ->map(fn ($id) => $questions->get($id))
            ->filter()
            ->map(function (Question $q) {
                $options = collect($q->mauvaises_reponses)
                    ->push($q->bonne_reponse)
                    ->shuffle()
                    ->values()
                    ->all();

                return [
                    'id' => $q->id,
                    'enonce' => $q->enonce,
                    'options' => $options,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Corrige une tentative : calcule le score serveur, marque la réussite,
     * complète le module si réussi, et réinitialise le chapitre au 3e échec.
     *
     * @param  array<int, string>  $reponses  question_id => réponse choisie
     * @return array<string, mixed>
     */
    public function corriger(TentativeQuiz $tentative, array $reponses): array
    {
        if (! $tentative->estEnCours()) {
            throw new HttpException(422, 'Cette tentative a déjà été soumise.');
        }

        $module = $tentative->module()->firstOrFail();
        $seuil = $module->seuil_reussite ?: 70;

        $questions = Question::whereIn('id', $tentative->questions_tirees)->get();

        $corrects = 0;
        $corrections = [];
        foreach ($questions as $q) {
            $rep = $reponses[$q->id] ?? null;
            $ok = $rep !== null && $rep === $q->bonne_reponse;
            $corrects += $ok ? 1 : 0;
            $corrections[] = [
                'question_id' => $q->id,
                'correct' => $ok,
                'bonne_reponse' => $q->bonne_reponse,
            ];
        }

        $total = max(1, $questions->count());
        $score = (int) round($corrects / $total * 100);
        $reussi = $score >= $seuil;

        return DB::transaction(function () use ($tentative, $module, $reponses, $score, $reussi, $seuil, $corrections) {
            $tentative->reponses = $reponses;
            $tentative->score = $score;
            $tentative->reussi = $reussi;
            $tentative->termine_le = now();
            $tentative->save();

            $reset = false;

            if ($reussi) {
                $progression = Progression::where('inscription_id', $tentative->inscription_id)
                    ->where('module_id', $module->id)
                    ->firstOrFail();
                $this->completion->terminerModule($progression, $score);
                $restantes = 0;
            } elseif ($tentative->numero >= TentativeQuiz::MAX_TENTATIVES) {
                $this->reinitialiserChapitre($tentative->inscription_id, $module);
                $reset = true;
                $restantes = TentativeQuiz::MAX_TENTATIVES; // le compteur repart de zéro
            } else {
                $restantes = TentativeQuiz::MAX_TENTATIVES - $tentative->numero;
            }

            return [
                'score' => $score,
                'seuil' => $seuil,
                'reussi' => $reussi,
                'reset_chapitre' => $reset,
                'tentatives_restantes' => $restantes,
                'corrections' => $corrections,
            ];
        });
    }

    /**
     * Réinitialise TOUT le chapitre en une seule transaction : progressions des
     * modules, checkpoints résolus, tentatives de quiz — puis journalise.
     * Appelé depuis la transaction de `corriger`.
     */
    private function reinitialiserChapitre(int $inscriptionId, Module $moduleQuiz): void
    {
        $moduleIds = Module::where('chapitre_id', $moduleQuiz->chapitre_id)->pluck('id');

        $progressions = Progression::where('inscription_id', $inscriptionId)
            ->whereIn('module_id', $moduleIds)
            ->get();
        $progIds = $progressions->pluck('id');

        CheckpointResolu::whereIn('progression_id', $progIds)->delete();
        TentativeQuiz::where('inscription_id', $inscriptionId)
            ->whereIn('module_id', $moduleIds)
            ->delete();
        Progression::whereIn('id', $progIds)->update([
            'statut' => 'non_commencee',
            'score' => null,
            'termine_le' => null,
        ]);

        ReinitialisationChapitre::create([
            'inscription_id' => $inscriptionId,
            'chapitre_id' => $moduleQuiz->chapitre_id,
            'module_quiz_id' => $moduleQuiz->id,
        ]);
    }
}
