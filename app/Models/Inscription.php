<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Inscription d'un utilisateur à une formation — table `inscriptions`.
 */
class Inscription extends Model
{
    protected $table = 'inscriptions';

    public $timestamps = false;

    protected $fillable = [
        'utilisateur_id',
        'formation_id',
        'statut',
        'objectif_quotidien',
        'inscrit_le',
        'termine_le',
    ];

    /**
     * L'apprenant inscrit — `withTrashed()` : le suivi et les rapports de
     * conformité doivent rester lisibles pour un employé désactivé.
     */
    public function utilisateur()
    {
        return $this->belongsTo(User::class, 'utilisateur_id')->withTrashed();
    }

    public function formation()
    {
        return $this->belongsTo(Formation::class, 'formation_id');
    }

    /**
     * Progressions module par module de cette inscription.
     */
    public function progressions()
    {
        return $this->hasMany(Progression::class, 'inscription_id');
    }

    /**
     * Tentatives de quiz noté de cette inscription (toutes formations confondues
     * du parcours), utilisées pour le compteur 3 tentatives / reset chapitre.
     */
    public function tentativesQuiz()
    {
        return $this->hasMany(TentativeQuiz::class, 'inscription_id');
    }

    /**
     * Progression en pourcentage : modules terminés / modules de la formation.
     *
     * Optimisé pour éviter les requêtes N+1 : passer les compteurs déjà agrégés
     * (`modulesTotal`, `modulesFaits`) depuis le contrôleur. À défaut, un calcul
     * de repli interroge les relations.
     */
    public function pourcentageProgression(?int $modulesTotal = null, ?int $modulesFaits = null): int
    {
        $total = $modulesTotal ?? $this->formation?->modules()->count() ?? 0;

        if ($total === 0) {
            return 0;
        }

        $faits = $modulesFaits ?? $this->progressions()->where('statut', 'terminee')->count();

        return (int) round(min($faits, $total) / $total * 100);
    }
}
