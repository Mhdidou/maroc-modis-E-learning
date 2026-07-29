<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tentatives de quiz noté, comptées en base (3 max avant reset du chapitre).
 * Le timer est démarré (`demarre_le`) et vérifié côté serveur ; le tirage des
 * questions (`questions_tirees`) est figé à l'ouverture pour empêcher la triche.
 * `score` et `reussi` sont calculés serveur à la correction.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tentatives_quiz', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inscription_id')
                ->constrained('inscriptions')
                ->cascadeOnDelete();
            $table->foreignId('module_id')             // module de type quiz
                ->constrained('modules')
                ->cascadeOnDelete();
            $table->unsignedTinyInteger('numero');     // 1, 2, 3 (repart à 1 après reset)
            $table->json('questions_tirees');          // IDs des questions tirées pour cette tentative
            $table->json('reponses')->nullable();      // réponses soumises (audit)
            $table->unsignedTinyInteger('score')->nullable();   // pourcentage
            $table->boolean('reussi')->nullable();
            $table->timestamp('demarre_le');
            $table->timestamp('termine_le')->nullable();

            $table->index(['inscription_id', 'module_id']);
            $table->index('module_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tentatives_quiz');
    }
};
