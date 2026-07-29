<?php

namespace App\Http\Resources;

use App\Models\TentativeQuiz;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Tentative de quiz en cours, telle qu'exposée au client au démarrage.
 * Ne contient JAMAIS les bonnes réponses (uniquement les énoncés + options,
 * fournis via `additional(['questions' => ...])`).
 *
 * @mixin TentativeQuiz
 */
class TentativeQuizResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'module_id' => $this->module_id,
            'numero' => $this->numero,
            'tentatives_max' => TentativeQuiz::MAX_TENTATIVES,
            'duree_minutes' => $this->whenLoaded('module', fn () => $this->module->duree_minutes),
            'demarre_le' => optional($this->demarre_le)->toIso8601String(),
        ];
    }
}
