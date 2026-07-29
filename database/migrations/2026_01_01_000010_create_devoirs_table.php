<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Soumissions de devoirs. L'apprenant soumet (fichier et/ou texte) → statut
 * `en_attente` → un formateur ou superviseur approuve/rejette avec commentaire.
 * Seule l'approbation débloque la complétion du module (côté serveur).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devoirs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('progression_id')
                ->constrained('progressions')
                ->cascadeOnDelete();
            $table->text('contenu_texte')->nullable();
            $table->string('chemin_fichier', 500)->nullable();
            $table->enum('statut', ['en_attente', 'approuve', 'rejete'])->default('en_attente');
            $table->text('commentaire')->nullable();          // motif d'approbation/rejet
            $table->foreignId('evalue_par')
                ->nullable()
                ->constrained('utilisateurs')
                ->nullOnDelete();
            $table->timestamp('soumis_le')->nullable();
            $table->timestamp('evalue_le')->nullable();

            $table->index('progression_id');
            $table->index('statut');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devoirs');
    }
};
