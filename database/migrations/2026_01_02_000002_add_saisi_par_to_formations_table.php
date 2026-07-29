<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Distingue le RESPONSABLE d'une formation de la personne qui l'a SAISIE.
 *
 * `cree_par` reste le responsable pédagogique : c'est son nom qui est attribué
 * à la formation et imprimé sur les certificats. L'administrateur du site
 * n'est pas un auteur de contenu — il dépanne, teste et supervise. Quand il
 * construit une formation à la place d'un formateur ou d'un superviseur peu à
 * l'aise avec l'outil, la formation est attribuée à cette personne
 * (`cree_par`) et `saisi_par` conserve la trace de qui a réellement opéré.
 *
 * NULL = le responsable a saisi lui-même (cas courant).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('formations', function (Blueprint $table) {
            $table->foreignId('saisi_par')
                ->nullable()
                ->after('cree_par')
                ->constrained('utilisateurs')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('formations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('saisi_par');
        });
    }
};
