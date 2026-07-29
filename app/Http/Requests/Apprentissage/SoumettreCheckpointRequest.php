<?php

namespace App\Http\Requests\Apprentissage;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Réponse à un quiz-surprise vidéo. Le client ne transmet que la réponse
 * choisie ; la correction est décidée serveur.
 */
class SoumettreCheckpointRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'reponse' => ['required', 'string', 'max:255'],
        ];
    }
}
