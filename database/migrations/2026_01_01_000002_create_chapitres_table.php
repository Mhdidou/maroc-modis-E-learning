<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Chapitres : niveau intermédiaire réintroduit entre Formation et Module.
 * L'ordre d'affichage est porté par `position`. C'est aussi l'unité de
 * réinitialisation en cas de 3 échecs à un quiz noté (reset de tout le chapitre).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chapitres', function (Blueprint $table) {
            $table->id();
            $table->foreignId('formation_id')
                ->constrained('formations')
                ->cascadeOnDelete();
            $table->string('titre', 200);
            $table->unsignedInteger('position')->default(0);
            $table->timestamp('cree_le')->nullable();
            $table->timestamp('mis_a_jour_le')->nullable();

            $table->index('formation_id');
            $table->index(['formation_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chapitres');
    }
};
