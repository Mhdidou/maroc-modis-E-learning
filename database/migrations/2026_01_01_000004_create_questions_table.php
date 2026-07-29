<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Banque de questions d'un module de type quiz. On y stocke N questions ;
 * chaque tentative en tire X aléatoirement (anti-mémorisation). La bonne
 * réponse ne quitte JAMAIS le serveur tant que la tentative n'est pas corrigée.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')
                ->constrained('modules')
                ->cascadeOnDelete();
            $table->text('enonce');
            $table->string('bonne_reponse', 255);
            $table->json('mauvaises_reponses');   // ex : ["choix1", "choix2", "choix3"]
            $table->timestamp('cree_le')->nullable();

            $table->index('module_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
