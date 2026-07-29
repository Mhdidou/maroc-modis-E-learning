<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Soumission de devoir — table `devoirs`. Approbation par formateur/superviseur
 * requise pour débloquer la complétion du module (côté serveur).
 */
class Devoir extends Model
{
    protected $table = 'devoirs';

    public $timestamps = false;

    public const STATUT_EN_ATTENTE = 'en_attente';

    public const STATUT_APPROUVE = 'approuve';

    public const STATUT_REJETE = 'rejete';

    protected $fillable = [
        'progression_id',
        'contenu_texte',
        'chemin_fichier',
        'nom_fichier',
        'statut',
        'commentaire',
        'evalue_par',
        'soumis_le',
        'evalue_le',
    ];

    protected function casts(): array
    {
        return [
            'soumis_le' => 'datetime',
            'evalue_le' => 'datetime',
        ];
    }

    public function progression()
    {
        return $this->belongsTo(Progression::class, 'progression_id');
    }

    /**
     * Le formateur/superviseur qui a évalué — `withTrashed()` : « qui a validé
     * ce devoir » doit rester traçable même après son départ.
     */
    public function evaluateur()
    {
        return $this->belongsTo(User::class, 'evalue_par')->withTrashed();
    }

    public function estApprouve(): bool
    {
        return $this->statut === self::STATUT_APPROUVE;
    }

    /**
     * Nom présenté au téléchargement : le nom d'origine quand on l'a, sinon un
     * repli lisible (les rendus antérieurs à la colonne `nom_fichier` n'en ont
     * pas). L'extension du fichier stocké est conservée dans les deux cas.
     */
    public function nomTelechargement(): string
    {
        if ($this->nom_fichier) {
            return $this->nom_fichier;
        }

        $extension = pathinfo($this->chemin_fichier ?? '', PATHINFO_EXTENSION);

        return 'devoir-'.$this->id.($extension ? '.'.$extension : '');
    }
}
