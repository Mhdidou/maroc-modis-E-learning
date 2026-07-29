<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Certificats délivrés pour une formation certifiante terminée. `numero_unique`
 * identifie le document (vérifiable) ; `expire_le` gère le renouvellement.
 * Le PDF est généré de façon asynchrone (queue) puis son chemin est stocké.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificats', function (Blueprint $table) {
            $table->id();
            // RESTRICT et non CASCADE : un certificat est une pièce d'audit. La
            // suppression physique d'un titulaire ou d'une formation doit échouer
            // bruyamment plutôt que d'effacer silencieusement la preuve qu'une
            // habilitation a été délivrée. Les employés se désactivent
            // (`utilisateurs.supprime_le`), les formations se dépublient.
            $table->foreignId('utilisateur_id')
                ->constrained('utilisateurs')
                ->restrictOnDelete();
            $table->foreignId('formation_id')
                ->constrained('formations')
                ->restrictOnDelete();
            $table->string('numero_unique', 40)->unique();
            $table->string('chemin_fichier', 500)->nullable(); // NULL tant que le PDF n'est pas généré
            $table->timestamp('delivre_le')->useCurrent();
            $table->timestamp('expire_le')->nullable();

            // Volontairement NON unique sur (utilisateur_id, formation_id) :
            // une recertification après expiration doit créer une nouvelle
            // ligne, l'historique complet des certifications étant exigé en
            // audit. Le certificat courant est celui au `delivre_le` le plus
            // récent ; l'unicité fonctionnelle (un seul certificat valable à la
            // fois) est garantie par le job GenererCertificat.
            $table->index(['utilisateur_id', 'formation_id']);
            $table->index('formation_id');
            $table->index('expire_le');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificats');
    }
};
