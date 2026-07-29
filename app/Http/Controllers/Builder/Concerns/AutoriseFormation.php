<?php

namespace App\Http\Controllers\Builder\Concerns;

use App\Models\Chapitre;
use App\Models\Formation;
use App\Models\Module;
use Illuminate\Support\Facades\Cache;

/**
 * Résout la formation parente d'un chapitre/module et applique la policy
 * `update`. Centralise l'autorisation des ressources filles de l'atelier.
 */
trait AutoriseFormation
{
    protected function formationDuChapitre(Chapitre $chapitre): Formation
    {
        $formation = $chapitre->formation()->firstOrFail();
        $this->authorize('update', $formation);

        return $formation;
    }

    protected function formationDuModule(Module $module): Formation
    {
        $chapitre = $module->chapitre()->firstOrFail();

        return $this->formationDuChapitre($chapitre);
    }

    protected function oublierCache(Formation $formation): void
    {
        Cache::forget("formation.structure.{$formation->id}");
    }
}
