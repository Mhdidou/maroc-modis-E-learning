<?php

namespace App\Http\Controllers\Builder;

use App\Http\Controllers\Builder\Concerns\AutoriseFormation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Builder\CheckpointRequest;
use App\Models\CheckpointVideo;
use App\Models\Module;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * CRUD des quiz-surprise vidéo (timestamps) d'un module vidéo.
 */
class CheckpointController extends Controller
{
    use AutoriseFormation;

    public function store(CheckpointRequest $request, Module $module): RedirectResponse
    {
        $formation = $this->formationDuModule($module);

        if (! $module->estVideo()) {
            throw new HttpException(422, 'Les points de contrôle ne concernent que les modules vidéo.');
        }

        $module->checkpointsVideo()->create($request->validated());
        $this->oublierCache($formation);

        return back()->with('status', 'Point de contrôle ajouté.');
    }

    public function update(CheckpointRequest $request, CheckpointVideo $checkpoint): RedirectResponse
    {
        $module = $checkpoint->module()->firstOrFail();
        $formation = $this->formationDuModule($module);

        $checkpoint->update($request->validated());
        $this->oublierCache($formation);

        return back()->with('status', 'Point de contrôle mis à jour.');
    }

    public function destroy(CheckpointVideo $checkpoint): RedirectResponse
    {
        $module = $checkpoint->module()->firstOrFail();
        $formation = $this->formationDuModule($module);

        $checkpoint->delete();
        $this->oublierCache($formation);

        return back()->with('status', 'Point de contrôle supprimé.');
    }
}
