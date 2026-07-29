<?php

namespace App\Http\Requests\Builder;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Création / renommage d'un chapitre.
 */
class ChapitreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'titre' => ['required', 'string', 'max:200'],
        ];
    }
}
