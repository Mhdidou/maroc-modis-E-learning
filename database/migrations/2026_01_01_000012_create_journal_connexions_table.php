<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Journal des connexions : une ligne par utilisateur et par jour. Alimente la
 * section « Activité durant la semaine » du tableau de bord.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_connexions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('utilisateur_id')
                ->constrained('utilisateurs')
                ->cascadeOnDelete();
            $table->date('jour');
            $table->timestamp('cree_le')->nullable();

            $table->unique(['utilisateur_id', 'jour']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_connexions');
    }
};
