<?php

namespace App\Http\Resources;

use App\Models\Devoir;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Soumission de devoir et son statut d'évaluation.
 *
 * @mixin Devoir
 */
class DevoirResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'progression_id' => $this->progression_id,
            'contenu_texte' => $this->contenu_texte,
            'a_fichier' => (bool) $this->chemin_fichier,
            'nom_fichier' => $this->nom_fichier,
            'statut' => $this->statut,
            'commentaire' => $this->commentaire,
            'soumis_le' => optional($this->soumis_le)->toIso8601String(),
            'evalue_le' => optional($this->evalue_le)->toIso8601String(),
            'evaluateur' => $this->whenLoaded('evaluateur', fn () => $this->evaluateur?->nom_complet),
        ];
    }
}
