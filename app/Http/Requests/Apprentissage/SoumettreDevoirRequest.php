<?php

namespace App\Http\Requests\Apprentissage;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Soumission d'un devoir : texte et/ou fichier (au moins l'un des deux).
 */
class SoumettreDevoirRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Taille maximale d'un rendu, en kilo-octets (100 Mo). Assez large pour une
     * photo de pièce cousue ou une courte vidéo de geste technique.
     */
    public const TAILLE_MAX_KO = 102400;

    public function rules(): array
    {
        return [
            'contenu_texte' => ['nullable', 'string', 'max:10000', 'required_without:fichier'],
            // Aucune restriction de format : sur le plateau, un rendu peut être
            // une photo, un scan, une vidéo, un tableur ou une archive. Le
            // fichier est stocké sur le disque privé `local` sous un nom haché
            // par Laravel, donc jamais servi ni exécuté par le serveur web ; le
            // seul garde-fou nécessaire est la taille.
            'fichier' => ['nullable', 'file', 'max:'.self::TAILLE_MAX_KO, 'required_without:contenu_texte'],
        ];
    }

    public function messages(): array
    {
        return [
            'contenu_texte.required_without' => 'Fournissez un texte ou un fichier.',
            'fichier.required_without' => 'Fournissez un fichier ou un texte.',
            'fichier.max' => 'Fichier trop volumineux (100 Mo maximum).',
        ];
    }
}
