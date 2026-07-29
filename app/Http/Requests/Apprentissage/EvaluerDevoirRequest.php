<?php

namespace App\Http\Requests\Apprentissage;

use App\Models\Devoir;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Approbation ou rejet d'un devoir par un formateur ou un superviseur (jamais
 * un apprenant). Un commentaire est exigé en cas de rejet.
 */
class EvaluerDevoirRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && ($user->isFormateur() || $user->isSuperviseur() || $user->isAdmin());
    }

    public function rules(): array
    {
        return [
            'decision' => ['required', Rule::in([Devoir::STATUT_APPROUVE, Devoir::STATUT_REJETE])],
            'commentaire' => ['nullable', 'string', 'max:2000', 'required_if:decision,'.Devoir::STATUT_REJETE],
        ];
    }

    public function messages(): array
    {
        return [
            'commentaire.required_if' => 'Un commentaire est obligatoire pour justifier un rejet.',
        ];
    }
}
