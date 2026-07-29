<?php

namespace App\Http\Controllers\Apprentissage;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProgressionResource;
use App\Models\Module;
use App\Services\MoteurCompletion;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Complétion d'un module PDF ou vidéo. Le quiz et le devoir se complètent
 * respectivement par la réussite d'une tentative et par l'approbation : ils
 * ne passent jamais par cet endpoint. Pour la vidéo, tous les checkpoints
 * doivent être résolus côté serveur.
 */
class ModuleCompletionController extends Controller
{
    public function terminer(Request $request, Module $module, MoteurCompletion $completion): ProgressionResource
    {
        if (! in_array($module->type, [Module::TYPE_PDF, Module::TYPE_VIDEO], true)) {
            throw new HttpException(422, 'Ce type de module se complète automatiquement (quiz réussi ou devoir approuvé).');
        }

        $completion->assurerAccessible($request->user(), $module);
        $progression = $completion->progression($request->user(), $module);

        if (! $completion->peutTerminer($module, $progression)) {
            throw new HttpException(422, 'Vous devez résoudre tous les points de contrôle de la vidéo avant de terminer ce module.');
        }

        $progression = $completion->terminerModule($progression);

        return new ProgressionResource($progression);
    }
}
