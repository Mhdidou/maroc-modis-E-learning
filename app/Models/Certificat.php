<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Certificat délivré à un apprenant — table `certificats`.
 */
class Certificat extends Model
{
    protected $table = 'certificats';

    public $timestamps = false;

    protected $fillable = [
        'utilisateur_id',
        'formation_id',
        'numero_unique',
        'chemin_fichier',
        'delivre_le',
        'expire_le',
    ];

    protected function casts(): array
    {
        return [
            'delivre_le' => 'datetime',
            'expire_le' => 'datetime',
        ];
    }

    /**
     * Le certificat est-il expiré (renouvellement nécessaire) ?
     */
    public function estExpire(): bool
    {
        return $this->expire_le !== null && $this->expire_le->isPast();
    }

    /**
     * Certificat encore valable — un certificat sans date d'expiration est
     * valable indéfiniment.
     */
    public function estValide(): bool
    {
        return ! $this->estExpire();
    }

    /**
     * Le certificat arrive-t-il à échéance dans les `$jours` prochains jours ?
     * Sert aux relances de renouvellement et à l'alerte visuelle « bientôt ».
     */
    public function expireBientot(int $jours = 60): bool
    {
        return $this->expire_le !== null
            && $this->estValide()
            && $this->expire_le->isBefore(now()->addDays($jours));
    }

    /**
     * État consolidé, unique vocabulaire partagé par l'API et l'interface.
     */
    public function statut(): string
    {
        return match (true) {
            $this->estExpire() => 'expire',
            $this->expireBientot() => 'bientot_expire',
            default => 'valide',
        };
    }

    /**
     * Certificats encore valables (jamais expirés). Utilisé par les compteurs :
     * un certificat expiré ne compte pas comme une certification acquise.
     */
    public function scopeValides(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->whereNull('expire_le')->orWhere('expire_le', '>', now());
        });
    }

    /**
     * Certificats dont la validité est dépassée.
     */
    public function scopeExpires(Builder $query): Builder
    {
        return $query->whereNotNull('expire_le')->where('expire_le', '<=', now());
    }

    /**
     * Le titulaire — `withTrashed()` obligatoire : un certificat reste
     * vérifiable publiquement (QR code, auditeur) après le départ de l'employé.
     * Sans cela le global scope de SoftDeletes masquerait le titulaire et la
     * page de vérification afficherait un certificat sans nom.
     */
    public function utilisateur()
    {
        return $this->belongsTo(User::class, 'utilisateur_id')->withTrashed();
    }

    public function formation()
    {
        return $this->belongsTo(Formation::class, 'formation_id');
    }
}
