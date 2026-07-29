<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Progression d'un apprenant sur un module donné — table `progressions`.
 * Sert au calcul de la progression en pourcentage d'une inscription.
 */
class Progression extends Model
{
    protected $table = 'progressions';

    public $timestamps = false;

    protected $fillable = [
        'inscription_id',
        'module_id',
        'statut',
        'score',
        'termine_le',
    ];

    protected function casts(): array
    {
        return [
            'termine_le' => 'datetime',
        ];
    }

    public function inscription()
    {
        return $this->belongsTo(Inscription::class, 'inscription_id');
    }

    public function module()
    {
        return $this->belongsTo(Module::class, 'module_id');
    }

    /**
     * Checkpoints vidéo résolus dans le cadre de cette progression.
     */
    public function checkpointsResolus()
    {
        return $this->hasMany(CheckpointResolu::class, 'progression_id');
    }

    /**
     * Soumissions de devoir rattachées (module de type devoir).
     */
    public function devoirs()
    {
        return $this->hasMany(Devoir::class, 'progression_id');
    }

    /**
     * Dernière soumission de devoir (la plus récente).
     */
    public function dernierDevoir()
    {
        return $this->hasOne(Devoir::class, 'progression_id')->latestOfMany('id');
    }
}
