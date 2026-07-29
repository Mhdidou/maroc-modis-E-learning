<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Quiz-surprise vidéo placé à un timestamp — table `checkpoints_video`.
 * `bonne_reponse`/`explication` ne sont exposés qu'après soumission de la réponse.
 */
class CheckpointVideo extends Model
{
    protected $table = 'checkpoints_video';

    const CREATED_AT = 'cree_le';

    const UPDATED_AT = 'mis_a_jour_le';

    protected $fillable = [
        'module_id',
        'position_secondes',
        'enonce',
        'bonne_reponse',
        'mauvaises_reponses',
        'explication',
    ];

    protected function casts(): array
    {
        return [
            'position_secondes' => 'integer',
            'mauvaises_reponses' => 'array',
        ];
    }

    public function module()
    {
        return $this->belongsTo(Module::class, 'module_id');
    }

    public function resolutions()
    {
        return $this->hasMany(CheckpointResolu::class, 'checkpoint_id');
    }
}
