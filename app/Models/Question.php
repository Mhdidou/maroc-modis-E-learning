<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Question de la banque d'un module quiz — table `questions`.
 * `bonne_reponse` ne doit jamais être sérialisée vers le client avant correction.
 */
class Question extends Model
{
    protected $table = 'questions';

    const CREATED_AT = 'cree_le';

    const UPDATED_AT = null;

    protected $fillable = [
        'module_id',
        'enonce',
        'bonne_reponse',
        'mauvaises_reponses',
    ];

    protected function casts(): array
    {
        return [
            'mauvaises_reponses' => 'array',
        ];
    }

    public function module()
    {
        return $this->belongsTo(Module::class, 'module_id');
    }
}
