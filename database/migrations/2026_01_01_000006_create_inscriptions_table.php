<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Inscription d'un apprenant à une formation (affectée par un superviseur/admin).
 * `objectif_quotidien` : nombre de modules/jour visé, fixé à l'affectation.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inscriptions', function (Blueprint $table) {
            $table->id();
            // RESTRICT : l'inscription porte toute la progression de l'apprenant
            // (progressions, tentatives de quiz, devoirs en cascade). C'est la
            // trace de « qui a suivi quelle formation, quand » — donnée d'audit.
            // Voir la même règle sur `certificats`.
            $table->foreignId('utilisateur_id')
                ->constrained('utilisateurs')
                ->restrictOnDelete();
            $table->foreignId('formation_id')
                ->constrained('formations')
                ->restrictOnDelete();
            $table->enum('statut', ['non_commencee', 'en_cours', 'terminee'])->default('non_commencee');
            $table->unsignedTinyInteger('objectif_quotidien')->default(3);
            $table->timestamp('inscrit_le')->useCurrent();
            $table->timestamp('termine_le')->nullable();

            $table->unique(['utilisateur_id', 'formation_id']);
            $table->index('formation_id');
            $table->index('statut');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inscriptions');
    }
};
