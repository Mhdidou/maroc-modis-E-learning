<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Modules : unité de contenu rattachée à un CHAPITRE (et non plus directement
 * à la formation). Quatre types : pdf, video, quiz, devoir.
 *
 * Les colonnes de configuration ci-dessous ne concernent que certains types
 * (nullables) et ne sont JAMAIS de source de vérité pour la validation, qui
 * reste 100 % serveur :
 *   - quiz   : nb_questions_tirees (X tirés dans la banque), seuil_reussite (%),
 *              duree_minutes (timer vérifié serveur).
 *   - devoir : consignes (énoncé rédigé par le formateur).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chapitre_id')
                ->constrained('chapitres')
                ->cascadeOnDelete();
            $table->enum('type', ['pdf', 'video', 'quiz', 'devoir']);
            $table->string('titre', 200);
            $table->string('contenu', 500)->nullable();   // chemin fichier (pdf/vidéo), NULL sinon
            $table->unsignedInteger('position')->default(0);

            // Configuration quiz noté (nullable hors type quiz).
            $table->unsignedSmallInteger('nb_questions_tirees')->nullable();
            $table->unsignedTinyInteger('seuil_reussite')->nullable();   // pourcentage
            $table->unsignedSmallInteger('duree_minutes')->nullable();   // timer

            // Consignes du devoir (nullable hors type devoir).
            $table->text('consignes')->nullable();

            $table->timestamp('cree_le')->nullable();
            $table->timestamp('mis_a_jour_le')->nullable();

            $table->index('chapitre_id');
            $table->index(['chapitre_id', 'position']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modules');
    }
};
