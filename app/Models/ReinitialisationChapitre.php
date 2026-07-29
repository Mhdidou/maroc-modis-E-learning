<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Log horodaté d'une réinitialisation de chapitre (3e échec au quiz noté) —
 * table `reinitialisations_chapitre`. Écrit dans la transaction de reset ;
 * ligne d'audit conservée indéfiniment.
 */
class ReinitialisationChapitre extends Model
{
    protected $table = 'reinitialisations_chapitre';

    const CREATED_AT = 'cree_le';

    const UPDATED_AT = null;

    protected $fillable = [
        'inscription_id',
        'chapitre_id',
        'module_quiz_id',
    ];

    public function inscription()
    {
        return $this->belongsTo(Inscription::class, 'inscription_id');
    }

    public function chapitre()
    {
        return $this->belongsTo(Chapitre::class, 'chapitre_id');
    }

    public function moduleQuiz()
    {
        return $this->belongsTo(Module::class, 'module_quiz_id');
    }
}
