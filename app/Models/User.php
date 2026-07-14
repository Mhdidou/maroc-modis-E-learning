<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Les rôles reconnus par la plateforme (miroir de l'ENUM SQL `utilisateurs.role`).
     */
    public const ROLE_ADMIN = 'admin';
    public const ROLE_SUPERVISEUR = 'superviseur';
    public const ROLE_FORMATEUR = 'formateur';
    public const ROLE_APPRENANT = 'apprenant';

    /**
     * Ce modèle est branché sur la table métier en français.
     */
    protected $table = 'utilisateurs';

    /**
     * Colonnes d'horodatage personnalisées du schéma.
     */
    const CREATED_AT = 'cree_le';
    const UPDATED_AT = 'mis_a_jour_le';

    /**
     * Attributs assignables en masse.
     *
     * @var list<string>
     */
    protected $fillable = [
        'nom_complet',
        'email',
        'mot_de_passe',
        'role',
        'domaine',
        'superviseur_id',
    ];

    /**
     * Attributs masqués lors de la sérialisation (JSON envoyé au front).
     *
     * @var list<string>
     */
    protected $hidden = [
        'mot_de_passe',
    ];

    /**
     * Attributs calculés exposés au front-end.
     * `name` permet aux composants React existants de continuer à lire user.name.
     *
     * @var list<string>
     */
    protected $appends = [
        'name',
    ];

    /**
     * Casts d'attributs.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'mot_de_passe' => 'hashed',
        ];
    }

    /**
     * Laravel s'attend à une colonne "password" : on la redirige vers `mot_de_passe`.
     */
    public function getAuthPassword(): string
    {
        return $this->mot_de_passe;
    }

    /**
     * Le schéma ne contient pas de colonne remember_token :
     * on désactive proprement la fonctionnalité « se souvenir de moi ».
     */
    public function getRememberTokenName(): string
    {
        return '';
    }

    /**
     * Alias `name` -> `nom_complet` pour compatibilité avec le front-end existant.
     */
    public function getNameAttribute(): ?string
    {
        return $this->nom_complet;
    }

    /**
     * Le superviseur rattaché à cet apprenant.
     */
    public function superviseur()
    {
        return $this->belongsTo(User::class, 'superviseur_id');
    }

    /**
     * Les apprenants rattachés à ce superviseur.
     */
    public function apprenants()
    {
        return $this->hasMany(User::class, 'superviseur_id');
    }

    /**
     * Les formations créées par ce formateur.
     */
    public function formations()
    {
        return $this->hasMany(Formation::class, 'cree_par');
    }

    /**
     * Les inscriptions de cet utilisateur (apprenant).
     */
    public function inscriptions()
    {
        return $this->hasMany(Inscription::class, 'utilisateur_id');
    }

    /* ------------------------------------------------------------------ */
    /*  Helpers de rôle                                                    */
    /* ------------------------------------------------------------------ */

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isSuperviseur(): bool
    {
        return $this->role === self::ROLE_SUPERVISEUR;
    }

    public function isFormateur(): bool
    {
        return $this->role === self::ROLE_FORMATEUR;
    }

    public function isApprenant(): bool
    {
        return $this->role === self::ROLE_APPRENANT;
    }

    /**
     * Seuls l'administrateur du site et les superviseurs peuvent créer
     * de nouveaux comptes.
     */
    public function peutGererUtilisateurs(): bool
    {
        return $this->isAdmin() || $this->isSuperviseur();
    }

    /**
     * Rôles qu'un utilisateur est autorisé à créer / gérer.
     *
     * L'admin est SUPÉRIEUR au superviseur : lui seul peut créer des
     * superviseurs. Un superviseur se limite aux formateurs et apprenants.
     *
     * @return list<string>
     */
    public function rolesGerables(): array
    {
        if ($this->isAdmin()) {
            return [self::ROLE_SUPERVISEUR, self::ROLE_FORMATEUR, self::ROLE_APPRENANT];
        }

        if ($this->isSuperviseur()) {
            return [self::ROLE_FORMATEUR, self::ROLE_APPRENANT];
        }

        return [];
    }
}
