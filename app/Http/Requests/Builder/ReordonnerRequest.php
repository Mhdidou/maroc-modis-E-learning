<?php

namespace App\Http\Requests\Builder;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Réordonnancement (drag-and-drop) : liste ordonnée d'identifiants. Les
 * positions sont réécrites côté serveur selon l'ordre reçu.
 */
class ReordonnerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ordre' => ['required', 'array', 'min:1'],
            'ordre.*' => ['integer'],
        ];
    }

    /**
     * @return array<int, int>
     */
    public function ordre(): array
    {
        return array_map('intval', $this->input('ordre', []));
    }
}
