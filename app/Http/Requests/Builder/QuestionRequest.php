<?php

namespace App\Http\Requests\Builder;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Création / mise à jour d'une question de la banque d'un quiz noté.
 */
class QuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'enonce' => ['required', 'string', 'max:1000'],
            'bonne_reponse' => ['required', 'string', 'max:255'],
            'mauvaises_reponses' => ['required', 'array', 'min:1', 'max:5'],
            'mauvaises_reponses.*' => ['required', 'string', 'max:255'],
        ];
    }
}
