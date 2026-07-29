<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nom d'origine du fichier rendu par l'apprenant.
 *
 * `chemin_fichier` stocke un nom haché généré par Laravel (protection contre
 * les extensions piégées et les collisions) : sans le nom d'origine, le
 * formateur téléchargerait « a7f3c9....bin » sans savoir de quoi il s'agit.
 * Tous les formats de rendu étant désormais acceptés, cette information est
 * la seule qui rende la pièce identifiable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devoirs', function (Blueprint $table) {
            $table->string('nom_fichier', 255)->nullable()->after('chemin_fichier');
        });
    }

    public function down(): void
    {
        Schema::table('devoirs', function (Blueprint $table) {
            $table->dropColumn('nom_fichier');
        });
    }
};
