<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Progression d'une inscription sur un module donné. Source de vérité unique de
 * l'avancement (jamais côté client). `score` sert aux modules quiz. La complétion
 * est décidée par le serveur (cascade module → chapitre → formation).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('progressions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inscription_id')
                ->constrained('inscriptions')
                ->cascadeOnDelete();
            $table->foreignId('module_id')
                ->constrained('modules')
                ->cascadeOnDelete();
            $table->enum('statut', ['non_commencee', 'en_cours', 'terminee'])->default('non_commencee');
            $table->unsignedTinyInteger('score')->nullable();
            $table->timestamp('termine_le')->nullable();

            $table->unique(['inscription_id', 'module_id']);
            $table->index('module_id');
            $table->index('statut');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('progressions');
    }
};
