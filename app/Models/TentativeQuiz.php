<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Tentative de quiz noté — table `tentatives_quiz`. Comptée en base (3 max).
 * Timer et tirage figés à l'ouverture ; score/réussite calculés serveur.
 */
class TentativeQuiz extends Model
{
    protected $table = 'tentatives_quiz';

    public $timestamps = false;

    /** Nombre de tentatives autorisées avant réinitialisation du chapitre. */
    public const MAX_TENTATIVES = 3;

    protected $fillable = [
        'inscription_id',
        'module_id',
        'numero',
        'questions_tirees',
        'reponses',
        'score',
        'reussi',
        'demarre_le',
        'termine_le',
    ];

    protected function casts(): array
    {
        return [
            'numero' => 'integer',
            'questions_tirees' => 'array',
            'reponses' => 'array',
            'score' => 'integer',
            'reussi' => 'boolean',
            'demarre_le' => 'datetime',
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
     * La tentative est-elle encore ouverte (démarrée, non corrigée) ?
     */
    public function estEnCours(): bool
    {
        return $this->termine_le === null;
    }
}
