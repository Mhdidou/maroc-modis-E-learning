<?php

namespace App\Http\Controllers\Builder;

use App\Http\Controllers\Builder\Concerns\AutoriseFormation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Builder\ChapitreRequest;
use App\Http\Requests\Builder\ReordonnerRequest;
use App\Models\Chapitre;
use App\Models\Formation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

/**
 * CRUD + réordonnancement des chapitres d'une formation (atelier formateur).
 */
class ChapitreController extends Controller
{
    use AutoriseFormation;

    public function store(ChapitreRequest $request, Formation $formation): RedirectResponse
    {
        $this->authorize('update', $formation);

        $position = (int) $formation->chapitres()->max('position');

        $formation->chapitres()->create([
            'titre' => $request->validated('titre'),
            'position' => $position + 1,
        ]);
        $this->oublierCache($formation);

        return back()->with('status', 'Chapitre ajouté.');
    }

    public function update(ChapitreRequest $request, Chapitre $chapitre): RedirectResponse
    {
        $formation = $this->formationDuChapitre($chapitre);

        $chapitre->update(['titre' => $request->validated('titre')]);
        $this->oublierCache($formation);

        return back()->with('status', 'Chapitre renommé.');
    }

    public function destroy(Chapitre $chapitre): RedirectResponse
    {
        $formation = $this->formationDuChapitre($chapitre);

        $chapitre->delete();
        $this->oublierCache($formation);

        return back()->with('status', 'Chapitre supprimé.');
    }

    /**
     * Réécrit les positions des chapitres selon l'ordre reçu (drag-and-drop).
     */
    public function reordonner(ReordonnerRequest $request, Formation $formation): RedirectResponse
    {
        $this->authorize('update', $formation);

        // On se limite aux chapitres appartenant réellement à la formation.
        $valides = $formation->chapitres()->pluck('id')->all();

        DB::transaction(function () use ($request, $valides) {
            foreach ($request->ordre() as $position => $id) {
                if (in_array($id, $valides, true)) {
                    Chapitre::where('id', $id)->update(['position' => $position]);
                }
            }
        });
        $this->oublierCache($formation);

        return back(303);
    }
}
