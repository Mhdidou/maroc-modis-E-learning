<?php

namespace App\Http\Controllers\Apprentissage;

use App\Http\Controllers\Controller;
use App\Http\Requests\Apprentissage\SoumettreCheckpointRequest;
use App\Models\CheckpointResolu;
use App\Models\CheckpointVideo;
use App\Services\MoteurCompletion;
use Illuminate\Http\JsonResponse;

/**
 * Soumission d'un quiz-surprise vidéo (formatif, bloquant). La correction est
 * décidée serveur ; une mauvaise réponse n'échoue pas le module : on enregistre
 * uniquement que le checkpoint est résolu. Bonne réponse + explication ne sont
 * renvoyées qu'APRÈS soumission.
 */
class CheckpointController extends Controller
{
    public function soumettre(
        SoumettreCheckpointRequest $request,
        CheckpointVideo $checkpoint,
        MoteurCompletion $completion,
    ): JsonResponse {
        $module = $checkpoint->module()->firstOrFail();

        $progression = $completion->progression($request->user(), $module);
        $completion->marquerEnCours($progression);

        $correct = trim($request->validated('reponse')) === $checkpoint->bonne_reponse;

        CheckpointResolu::updateOrCreate(
            ['progression_id' => $progression->id, 'checkpoint_id' => $checkpoint->id],
            ['bonne_reponse' => $correct, 'resolu_le' => now()],
        );

        return response()->json([
            'correct' => $correct,
            'bonne_reponse' => $checkpoint->bonne_reponse,
            'explication' => $checkpoint->explication,
        ]);
    }
}
