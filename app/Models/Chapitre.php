<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Chapitre d'une formation — table `chapitres`. Niveau intermédiaire entre
 * Formation et Module ; unité de réinitialisation en cas de 3 échecs à un quiz.
 */
class Chapitre extends Model
{
    protected $table = 'chapitres';

    const CREATED_AT = 'cree_le';

    const UPDATED_AT = 'mis_a_jour_le';

    protected $fillable = [
        'formation_id',
        'titre',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
        ];
    }

    public function formation()
    {
        return $this->belongsTo(Formation::class, 'formation_id');
    }

    /**
     * Modules ordonnés du chapitre.
     */
    public function modules()
    {
        return $this->hasMany(Module::class, 'chapitre_id');
    }
}
