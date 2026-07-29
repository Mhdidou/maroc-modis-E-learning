<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Résolution d'un checkpoint vidéo par une progression — table `checkpoints_resolus`.
 * Sa présence conditionne la complétion du module vidéo (côté serveur).
 */
class CheckpointResolu extends Model
{
    protected $table = 'checkpoints_resolus';

    public $timestamps = false;

    protected $fillable = [
        'progression_id',
        'checkpoint_id',
        'bonne_reponse',
        'resolu_le',
    ];

    protected function casts(): array
    {
        return [
            'bonne_reponse' => 'boolean',
            'resolu_le' => 'datetime',
        ];
    }

    public function progression()
    {
        return $this->belongsTo(Progression::class, 'progression_id');
    }

    public function checkpoint()
    {
        return $this->belongsTo(CheckpointVideo::class, 'checkpoint_id');
    }
}
