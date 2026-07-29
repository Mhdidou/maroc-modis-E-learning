<?php

namespace App\Http\Requests\Builder;

use App\Models\Module;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Création / mise à jour d'un module. Les champs de configuration ne sont
 * requis que pour le type quiz ; `consignes` sert au devoir ; `contenu` au
 * pdf/vidéo.
 */
class ModuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in([
                Module::TYPE_PDF, Module::TYPE_VIDEO, Module::TYPE_QUIZ, Module::TYPE_DEVOIR,
            ])],
            'titre' => ['required', 'string', 'max:200'],
            'consignes' => ['nullable', 'string', 'max:5000'],
            'nb_questions_tirees' => ['nullable', 'integer', 'min:1', 'max:100'],
            'seuil_reussite' => ['nullable', 'integer', 'min:1', 'max:100'],
            'duree_minutes' => ['nullable', 'integer', 'min:1', 'max:240'],
        ];
    }
}
