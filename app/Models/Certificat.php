<?php

namespace App\Models;

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
        'chemin_fichier',
        'delivre_le',
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
