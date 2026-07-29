<?php

namespace App\Http\Requests\Builder;

use App\Models\Formation;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Création / mise à jour des métadonnées d'une formation (atelier formateur).
 */
class FormationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // périmètre déjà restreint par le middleware + policy contrôleur
    }

    public function rules(): array
    {
        $regles = [
            'titre' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:5000'],
            'type' => ['required', Rule::in([Formation::TYPE_CERTIFIANTE, Formation::TYPE_NON_CERTIFIANTE])],
            'validite_mois' => ['nullable', 'integer', 'min:1', 'max:120'],
        ];

        // Seul l'admin choisit le responsable, et il DOIT le faire : il n'est
        // pas auteur de contenu, toute formation qu'il saisit est construite
        // pour le compte d'un formateur ou d'un superviseur. Un formateur, lui,
        // ne peut pas réattribuer sa formation à quelqu'un d'autre — le champ
        // est simplement ignoré.
        if ($this->user()?->isAdmin()) {
            $regles['cree_par'] = [
                'required',
                'integer',
                Rule::exists('utilisateurs', 'id')
                    ->whereNull('supprime_le')
                    ->whereIn('role', [User::ROLE_FORMATEUR, User::ROLE_SUPERVISEUR]),
            ];
        }

        return $regles;
    }

    public function messages(): array
    {
        return [
            'cree_par.required' => 'Choisissez le formateur ou le superviseur responsable de cette formation.',
            'cree_par.exists' => 'Le responsable doit être un formateur ou un superviseur actif.',
        ];
    }
}
