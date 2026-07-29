<?php

namespace App\Http\Requests\Builder;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Création / mise à jour d'un quiz-surprise vidéo à un timestamp donné.
 */
class CheckpointRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'position_secondes' => ['required', 'integer', 'min:0', 'max:86400'],
            'enonce' => ['required', 'string', 'max:1000'],
            'bonne_reponse' => ['required', 'string', 'max:255'],
            'mauvaises_reponses' => ['required', 'array', 'min:1', 'max:5'],
            'mauvaises_reponses.*' => ['required', 'string', 'max:255'],
            'explication' => ['required', 'string', 'max:2000'],
        ];
    }
}
