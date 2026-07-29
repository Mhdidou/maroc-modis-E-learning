<?php

namespace App\Http\Controllers\Builder;

use App\Http\Controllers\Builder\Concerns\AutoriseFormation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Builder\QuestionRequest;
use App\Models\Module;
use App\Models\Question;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * CRUD de la banque de questions d'un module quiz noté.
 */
class QuestionController extends Controller
{
    use AutoriseFormation;

    public function store(QuestionRequest $request, Module $module): RedirectResponse
    {
        $formation = $this->formationDuModule($module);

        if (! $module->estQuiz()) {
            throw new HttpException(422, 'Les questions ne concernent que les modules quiz.');
        }

        $module->questions()->create($request->validated());
        $this->oublierCache($formation);

        return back()->with('status', 'Question ajoutée.');
    }

    public function update(QuestionRequest $request, Question $question): RedirectResponse
    {
        $module = $question->module()->firstOrFail();
        $formation = $this->formationDuModule($module);

        $question->update($request->validated());
        $this->oublierCache($formation);

        return back()->with('status', 'Question mise à jour.');
    }

    public function destroy(Question $question): RedirectResponse
    {
        $module = $question->module()->firstOrFail();
        $formation = $this->formationDuModule($module);

        $question->delete();
        $this->oublierCache($formation);

        return back()->with('status', 'Question supprimée.');
    }
}
