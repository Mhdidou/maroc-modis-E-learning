<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Quiz-surprise vidéo (formatif, bloquant). Le formateur place une question à
 * un timestamp précis (`position_secondes`) et rédige l'explication de la bonne
 * réponse. À la lecture, l'atteinte du checkpoint impose une pause dure ; un
 * checkpoint non résolu bloque la complétion du module.
 *
 * `bonne_reponse`, `mauvaises_reponses` et `explication` ne sont exposés au
 * client qu'APRÈS soumission de la réponse (jamais avant).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checkpoints_video', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')             // module de type video
                ->constrained('modules')
                ->cascadeOnDelete();
            $table->unsignedInteger('position_secondes');
            $table->text('enonce');
            $table->string('bonne_reponse', 255);
            $table->json('mauvaises_reponses');
            $table->text('explication');               // affichée après réponse
            $table->timestamp('cree_le')->nullable();
            $table->timestamp('mis_a_jour_le')->nullable();

            $table->index('module_id');
            $table->unique(['module_id', 'position_secondes']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checkpoints_video');
    }
};
