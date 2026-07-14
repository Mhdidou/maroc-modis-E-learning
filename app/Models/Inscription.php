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
        'inscrit_le',
        'termine_le',
    ];

    public function utilisateur()
    {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }

    public function formation()
    {
        return $this->belongsTo(Formation::class, 'formation_id');
    }
}
