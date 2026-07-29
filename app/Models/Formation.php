<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Formation métier (table `formations`). Racine de Formation → Chapitre → Module.
 */
class Formation extends Model
{
    protected $table = 'formations';

    const CREATED_AT = 'cree_le';

    const UPDATED_AT = 'mis_a_jour_le';

    public const TYPE_CERTIFIANTE = 'certifiante';

    public const TYPE_NON_CERTIFIANTE = 'non_certifiante';

    public const STATUT_BROUILLON = 'brouillon';

    public const STATUT_PUBLIE = 'publie';

    protected $fillable = [
        'titre',
        'description',
        'type',
        'statut',
        'validite_mois',
        'cree_par',
        'saisi_par',
    ];

    /**
     * Le formateur auteur de la formation. `withTrashed()` : le nom du formateur
     * est imprimé sur le certificat, il doit survivre à la désactivation de son
     * compte.
     */
    public function auteur()
    {
        return $this->belongsTo(User::class, 'cree_par')->withTrashed();
    }

    /**
     * L'opérateur qui a réellement saisi la formation quand ce n'est pas le
     * responsable (typiquement l'admin dépannant un formateur). NULL sinon.
     */
    public function operateur()
    {
        return $this->belongsTo(User::class, 'saisi_par')->withTrashed();
    }

    /**
     * La formation est-elle attribuée à un responsable pédagogique ?
     *
     * L'admin du site n'est pas un auteur : tant qu'une formation lui reste
     * rattachée, elle est considérée comme non attribuée et ne peut pas être
     * publiée — son nom figurerait sinon sur les certificats délivrés.
     */
    public function estAttribuee(): bool
    {
        $responsable = $this->auteur;

        return $responsable !== null && ! $responsable->isAdmin();
    }

    /**
     * Chapitres ordonnés de la formation.
     */
    public function chapitres()
    {
        return $this->hasMany(Chapitre::class, 'formation_id');
    }

    /**
     * Tous les modules de la formation, à travers ses chapitres.
     * Conserve l'API `$formation->modules()` utilisée ailleurs dans le code.
     */
    public function modules()
    {
        return $this->hasManyThrough(
            Module::class,
            Chapitre::class,
            'formation_id',   // FK sur chapitres
            'chapitre_id',    // FK sur modules
            'id',             // clé locale formations
            'id'              // clé locale chapitres
        );
    }

    public function inscriptions()
    {
        return $this->hasMany(Inscription::class, 'formation_id');
    }

    /**
     * Les certificats délivrés au titre de cette formation.
     */
    public function certificats()
    {
        return $this->hasMany(Certificat::class, 'formation_id');
    }

    /**
     * La formation porte-t-elle de l'historique de formation (certificats
     * délivrés ou apprenants inscrits) ? Si oui, elle n'est plus supprimable :
     * il faut la dépublier pour la retirer du catalogue.
     */
    public function porteHistorique(): bool
    {
        return $this->certificats()->exists() || $this->inscriptions()->exists();
    }

    public function isCertifiante(): bool
    {
        return $this->type === self::TYPE_CERTIFIANTE;
    }

    public function estPubliee(): bool
    {
        return $this->statut === self::STATUT_PUBLIE;
    }
}
