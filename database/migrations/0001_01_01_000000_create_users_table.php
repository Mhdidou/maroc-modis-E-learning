<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table métier des utilisateurs (les 3 acteurs + l'admin du site), en français.
 * Remplace la table `users` par défaut de Laravel : le modèle App\Models\User
 * est branché sur `utilisateurs` avec les colonnes `mot_de_passe`, `cree_le`, etc.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('utilisateurs', function (Blueprint $table) {
            $table->id();
            $table->string('nom_complet', 150);
            $table->string('email', 150)->unique();
            $table->string('mot_de_passe');
            $table->enum('role', ['admin', 'apprenant', 'formateur', 'superviseur'])
                ->default('apprenant');
            $table->string('domaine', 100)->nullable();       // ex : Couture, Coupe, Qualité
            $table->foreignId('superviseur_id')               // apprenant -> superviseur
                ->nullable()
                ->constrained('utilisateurs')
                ->nullOnDelete();
            $table->timestamp('cree_le')->nullable();
            $table->timestamp('mis_a_jour_le')->nullable();

            // Désactivation (soft delete). Un employé qui quitte le plateau est
            // désactivé, jamais supprimé : ses inscriptions et ses certificats
            // sont des pièces d'audit (ISO 9001, formations sécurité) qui doivent
            // survivre au départ. L'email reste unique sur les comptes désactivés
            // afin qu'un retour réactive le compte d'origine plutôt que d'en créer
            // un second, ce qui scinderait l'historique de formation.
            $table->timestamp('supprime_le')->nullable();

            $table->index('role');
            $table->index('superviseur_id');
            $table->index('supprime_le');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('utilisateurs');
    }
};
