<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

/**
 * Module de formation, rattaché à un CHAPITRE — table `modules`.
 * Types : pdf, video, quiz, devoir.
 */
class Module extends Model
{
    protected $table = 'modules';

    const CREATED_AT = 'cree_le';

    const UPDATED_AT = 'mis_a_jour_le';

    public const TYPE_PDF = 'pdf';

    public const TYPE_VIDEO = 'video';

    public const TYPE_QUIZ = 'quiz';

    public const TYPE_DEVOIR = 'devoir';

    protected $fillable = [
        'chapitre_id',
        'type',
        'titre',
        'contenu',
        'position',
        'nb_questions_tirees',
        'seuil_reussite',
        'duree_minutes',
        'consignes',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'nb_questions_tirees' => 'integer',
            'seuil_reussite' => 'integer',
            'duree_minutes' => 'integer',
        ];
    }

    /**
     * Chapitre parent.
     */
    public function chapitre()
    {
        return $this->belongsTo(Chapitre::class, 'chapitre_id');
    }

    /**
     * Formation grand-parente (à travers le chapitre).
     */
    public function formation(): HasOneThrough
    {
        return $this->hasOneThrough(
            Formation::class,
            Chapitre::class,
            'id',             // clé locale chapitres (référencée par modules.chapitre_id)
            'id',             // clé locale formations
            'chapitre_id',    // FK locale modules
            'formation_id'    // FK sur chapitres
        );
    }

    /**
     * Banque de questions (module de type quiz).
     */
    public function questions()
    {
        return $this->hasMany(Question::class, 'module_id');
    }

    /**
     * Checkpoints vidéo (module de type video).
     */
    public function checkpointsVideo()
    {
        return $this->hasMany(CheckpointVideo::class, 'module_id');
    }

    public function progressions()
    {
        return $this->hasMany(Progression::class, 'module_id');
    }

    public function tentativesQuiz()
    {
        return $this->hasMany(TentativeQuiz::class, 'module_id');
    }

    public function estQuiz(): bool
    {
        return $this->type === self::TYPE_QUIZ;
    }

    public function estVideo(): bool
    {
        return $this->type === self::TYPE_VIDEO;
    }

    public function estDevoir(): bool
    {
        return $this->type === self::TYPE_DEVOIR;
    }

    /**
     * Les types de modules qui acceptent un fichier importé dans `contenu`.
     * Pour pdf/vidéo c'est le support principal ; pour un devoir c'est une
     * pièce jointe d'explication facultative (énoncé filmé, sujet en PDF).
     */
    public function accepteFichier(): bool
    {
        return in_array($this->type, [self::TYPE_PDF, self::TYPE_VIDEO, self::TYPE_DEVOIR], true);
    }

    /**
     * Extensions acceptées pour le fichier de ce module. Un devoir accepte les
     * deux familles : le formateur explique au choix par vidéo ou par PDF.
     */
    public function extensionsAcceptees(): array
    {
        return match ($this->type) {
            self::TYPE_VIDEO => ['mp4', 'webm', 'ogg', 'mov'],
            self::TYPE_PDF => ['pdf'],
            self::TYPE_DEVOIR => ['mp4', 'webm', 'ogg', 'mov', 'pdf'],
            default => [],
        };
    }

    /**
     * Nature de la pièce jointe d'un devoir, déduite de l'extension du fichier
     * stocké : détermine si le front affiche un lecteur vidéo ou un lien PDF.
     * Renvoie null quand aucun fichier n'est attaché.
     */
    public function typePieceJointe(): ?string
    {
        if (! $this->contenu) {
            return null;
        }

        $extension = strtolower(pathinfo(parse_url($this->contenu, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));

        return match (true) {
            $extension === 'pdf' => self::TYPE_PDF,
            in_array($extension, ['mp4', 'webm', 'ogg', 'mov'], true) => self::TYPE_VIDEO,
            default => null,
        };
    }
}
