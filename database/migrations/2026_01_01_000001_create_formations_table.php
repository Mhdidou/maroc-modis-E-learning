<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Formations : certifiante | non-certifiante, avec cycle de vie brouillon | publié
 * (le formateur construit en brouillon puis publie). Racine de la hiérarchie
 * Formation → Chapitre → Module.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('formations', function (Blueprint $table) {
            $table->id();
            $table->string('titre', 200);
            $table->text('description')->nullable();
            $table->enum('type', ['certifiante', 'non_certifiante'])->default('non_certifiante');
            $table->enum('statut', ['brouillon', 'publie'])->default('brouillon');
            $table->unsignedSmallInteger('validite_mois')->nullable(); // durée de validité du certificat
            // RESTRICT : en CASCADE, supprimer physiquement un formateur emportait
            // tout son catalogue — et avec lui la progression et les certificats
            // des apprenants. Un formateur qui part se désactive.
            $table->foreignId('cree_par')                              // formateur auteur
                ->constrained('utilisateurs')
                ->restrictOnDelete();
            $table->timestamp('cree_le')->nullable();
            $table->timestamp('mis_a_jour_le')->nullable();

            $table->index('cree_par');
            $table->index('statut');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('formations');
    }
};
