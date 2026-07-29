<?php

namespace App\Http\Requests\Apprentissage;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Soumission d'une tentative de quiz noté. `confirmation` (checkbox obligatoire)
 * doit être acceptée. Le score, le seuil et la réussite sont calculés serveur.
 */
class SoumettreQuizRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'confirmation' => ['accepted'],
            'reponses' => ['required', 'array', 'min:1'],
            'reponses.*' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'confirmation.accepted' => 'Vous devez confirmer avant d’envoyer vos réponses.',
        ];
    }

    /**
     * Réponses indexées par identifiant de question (clés entières).
     *
     * @return array<int, string>
     */
    public function reponses(): array
    {
        $reponses = [];
        foreach ((array) $this->input('reponses', []) as $questionId => $valeur) {
            $reponses[(int) $questionId] = is_string($valeur) ? $valeur : '';
        }

        return $reponses;
    }
}
