<?php

namespace App\Http\Resources;

use App\Models\Progression;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * État serveur de la progression d'un apprenant sur un module.
 *
 * @mixin Progression
 */
class ProgressionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'module_id' => $this->module_id,
            'statut' => $this->statut,
            'score' => $this->score,
            'termine_le' => optional($this->termine_le)->toIso8601String(),
        ];
    }
}
