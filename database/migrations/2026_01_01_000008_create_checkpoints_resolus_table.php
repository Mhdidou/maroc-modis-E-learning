<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Trace serveur des checkpoints vidéo résolus par une progression. Une mauvaise
 * réponse n'échoue pas le module : on n'enregistre QUE le fait que le checkpoint
 * a été résolu. Tant qu'un checkpoint du module n'est pas ici, le module ne peut
 * pas passer « terminé ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checkpoints_resolus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('progression_id')
                ->constrained('progressions')
                ->cascadeOnDelete();
            $table->foreignId('checkpoint_id')
                ->constrained('checkpoints_video')
                ->cascadeOnDelete();
            $table->boolean('bonne_reponse')->default(false); // pour info/statistiques
            $table->timestamp('resolu_le')->nullable();

            $table->unique(['progression_id', 'checkpoint_id']);
            $table->index('checkpoint_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checkpoints_resolus');
    }
};
