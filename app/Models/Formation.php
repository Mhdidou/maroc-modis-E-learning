<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Formation métier (branchée sur la table `formations`).
 */
class Formation extends Model
{
    protected $table = 'formations';

    const CREATED_AT = 'cree_le';
    const UPDATED_AT = 'mis_a_jour_le';

    protected $fillable = [
        'titre',
        'description',
        'type',
        'cree_par',
    ];

    /**
     * Le formateur auteur de la formation.
     */
    public function auteur()
    {
        return $this->belongsTo(User::class, 'cree_par');
    }

    public function modules()
    {
        return $this->hasMany(Module::class, 'formation_id');
    }

    public function inscriptions()
    {
        return $this->hasMany(Inscription::class, 'formation_id');
    }
}
