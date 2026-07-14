<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Module de formation (pdf / video / quiz) — table `modules`.
 */
class Module extends Model
{
    protected $table = 'modules';

    const CREATED_AT = 'cree_le';
    const UPDATED_AT = 'mis_a_jour_le';

    protected $fillable = [
        'formation_id',
        'type',
        'titre',
        'contenu',
        'position',
    ];

    public function formation()
    {
        return $this->belongsTo(Formation::class, 'formation_id');
    }
}
