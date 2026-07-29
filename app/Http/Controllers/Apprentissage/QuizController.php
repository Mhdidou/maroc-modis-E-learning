<?php

namespace App\Http\Controllers\Apprentissage;

use App\Http\Controllers\Controller;
use App\Http\Requests\Apprentissage\SoumettreQuizRequest;
use App\Http\Resources\TentativeQuizResource;
use App\Models\Module;
use App\Models\TentativeQuiz;
use App\Services\MoteurQuiz;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Quiz noté (évaluatif). Démarrage (tirage + timer serveur) et soumission
 * (score, seuil, réussite calculés serveur ; reset du chapitre au 3e échec).
 */
class QuizController extends Controller
{
    public function demarrer(Request $request, Module $module, MoteurQuiz $moteur): JsonResource
    {
        $tentative = $moteur->demarrer($request->user(), $module);
        $tentative->setRelation('module', $module);

        $questions = $moteur->questionsPourAffichage($tentative);

        return (new TentativeQuizResource($tentative))->additional([
            'questions' => $questions,
            'tentatives_restantes' => TentativeQuiz::MAX_TENTATIVES - $tentative->numero + 1,
        ]);
    }

    public function soumettre(SoumettreQuizRequest $request, TentativeQuiz $tentative, MoteurQuiz $moteur): JsonResponse
    {
        // Propriété : la tentative doit appartenir à l'apprenant connecté.
        $proprietaire = $tentative->inscription()->value('utilisateur_id');
        if ($proprietaire !== $request->user()->id) {
            throw new HttpException(403, 'Cette tentative ne vous appartient pas.');
        }

        $resultat = $moteur->corriger($tentative, $request->reponses());

        return response()->json($resultat);
    }
}
