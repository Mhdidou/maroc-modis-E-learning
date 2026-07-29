<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Journal des connexions — table `journal_connexions`.
 * Une ligne par utilisateur et par jour (contrainte d'unicité) : sert à
 * mesurer l'activité de la semaine (nombre de jours de connexion).
 */
class JournalConnexion extends Model
{
    protected $table = 'journal_connexions';

    // Le schéma ne possède qu'une colonne d'horodatage : `cree_le` (pas de mise à jour).
    const CREATED_AT = 'cree_le';

    const UPDATED_AT = null;

    protected $fillable = [
        'utilisateur_id',
        'jour',
    ];

    protected function casts(): array
    {
        return [
            'jour' => 'date',
        ];
    }

    public function utilisateur()
    {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }
}
