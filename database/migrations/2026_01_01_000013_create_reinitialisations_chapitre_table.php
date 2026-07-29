<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Log horodaté des réinitialisations de chapitre déclenchées par un 3e échec à
 * un quiz noté. Écrit dans la même DB::transaction() que la remise à zéro
 * (progressions du chapitre + checkpoints résolus + tentatives). Trace d'audit :
 * on ne supprime jamais ces lignes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reinitialisations_chapitre', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inscription_id')
                ->constrained('inscriptions')
                ->cascadeOnDelete();
            $table->foreignId('chapitre_id')
                ->constrained('chapitres')
                ->cascadeOnDelete();
            $table->foreignId('module_quiz_id')        // quiz ayant déclenché le reset
                ->constrained('modules')
                ->cascadeOnDelete();
            $table->timestamp('cree_le')->nullable();

            $table->index('inscription_id');
            $table->index('chapitre_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reinitialisations_chapitre');
    }
};
