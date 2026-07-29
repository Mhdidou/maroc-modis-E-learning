<?php

namespace App\Http\Controllers\Builder;

use App\Http\Controllers\Builder\Concerns\AutoriseFormation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Builder\ModuleRequest;
use App\Http\Requests\Builder\ReordonnerRequest;
use App\Models\Chapitre;
use App\Models\Module;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * CRUD + réordonnancement des modules à l'intérieur d'un chapitre.
 */
class ModuleController extends Controller
{
    use AutoriseFormation;

    public function store(ModuleRequest $request, Chapitre $chapitre): RedirectResponse
    {
        $formation = $this->formationDuChapitre($chapitre);

        $position = (int) $chapitre->modules()->max('position');

        $chapitre->modules()->create($this->donnees($request) + ['position' => $position + 1]);
        $this->oublierCache($formation);

        return back()->with('status', 'Module ajouté.');
    }

    public function update(ModuleRequest $request, Module $module): RedirectResponse
    {
        $formation = $this->formationDuModule($module);

        $module->update($this->donnees($request));
        $this->oublierCache($formation);

        return back()->with('status', 'Module mis à jour.');
    }

    public function destroy(Module $module): RedirectResponse
    {
        $formation = $this->formationDuModule($module);

        $module->delete();
        $this->oublierCache($formation);

        return back()->with('status', 'Module supprimé.');
    }

    /**
     * Téléverse le fichier (pdf/vidéo) d'un module : stockage sur le disque
     * public, mise à jour de `contenu` avec l'URL, suppression de l'ancien
     * fichier importé. Remplace la saisie manuelle d'un chemin.
     */
    public function televerser(Request $request, Module $module): RedirectResponse
    {
        $formation = $this->formationDuModule($module);

        if (! $module->accepteFichier()) {
            throw new HttpException(422, 'Ce type de module n’accepte pas de fichier.');
        }

        $mimes = $module->extensionsAcceptees();

        // Un devoir accepte vidéo ou PDF : on ne connaît la nature du fichier
        // qu'une fois déposé, donc on retient la limite la plus large.
        $tailleMax = in_array('mp4', $mimes, true) ? 512000 : 20480; // Ko

        $request->validate([
            'fichier' => ['required', 'file', Rule::file()->extensions($mimes)->max($tailleMax)],
        ], [
            'fichier.extensions' => 'Format non pris en charge ('.implode(', ', $mimes).').',
        ]);

        // Supprime l'ancien fichier importé. On repasse par le chemin de l'URL
        // plutôt que par un préfixe littéral : une adresse absolue héritée
        // (http://hôte/storage/...) ne commençait pas par « /storage/ », le
        // nettoyage était donc silencieusement ignoré et l'ancienne vidéo
        // restait sur le disque à chaque remplacement.
        if ($module->contenu) {
            $cheminUrl = parse_url($module->contenu, PHP_URL_PATH) ?: '';

            if (str_starts_with($cheminUrl, '/storage/')) {
                Storage::disk('public')->delete(substr($cheminUrl, strlen('/storage/')));
            }
        }

        $chemin = $request->file('fichier')->store("modules/{$module->type}", 'public');

        $module->update(['contenu' => Storage::disk('public')->url($chemin)]);
        $this->oublierCache($formation);

        return back()->with('status', 'Fichier importé.');
    }

    public function reordonner(ReordonnerRequest $request, Chapitre $chapitre): RedirectResponse
    {
        $formation = $this->formationDuChapitre($chapitre);

        $valides = $chapitre->modules()->pluck('id')->all();

        DB::transaction(function () use ($request, $valides) {
            foreach ($request->ordre() as $position => $id) {
                if (in_array($id, $valides, true)) {
                    Module::where('id', $id)->update(['position' => $position]);
                }
            }
        });
        $this->oublierCache($formation);

        return back(303);
    }

    /**
     * Champs pertinents selon le type (les configs non concernées restent nulles).
     *
     * @return array<string, mixed>
     */
    private function donnees(ModuleRequest $request): array
    {
        $v = $request->validated();
        $type = $v['type'];

        // `contenu` (pdf/vidéo) n'est plus géré ici : il est la propriété exclusive
        // de l'endpoint d'upload (televerser).
        return [
            'type' => $type,
            'titre' => $v['titre'],
            'consignes' => $type === Module::TYPE_DEVOIR ? ($v['consignes'] ?? null) : null,
            'nb_questions_tirees' => $type === Module::TYPE_QUIZ ? ($v['nb_questions_tirees'] ?? null) : null,
            'seuil_reussite' => $type === Module::TYPE_QUIZ ? ($v['seuil_reussite'] ?? null) : null,
            'duree_minutes' => $type === Module::TYPE_QUIZ ? ($v['duree_minutes'] ?? null) : null,
        ];
    }
}
