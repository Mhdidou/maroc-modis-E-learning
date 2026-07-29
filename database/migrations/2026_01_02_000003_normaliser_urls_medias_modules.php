<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Ramène les médias de modules à une URL RELATIVE (/storage/...).
 *
 * Le disque `public` construisait ses URL à partir d'APP_URL, ce qui gravait
 * l'hôte dans la base : une vidéo importée pendant que APP_URL valait
 * « http://localhost » restait pointée sur cet hôte même servie depuis
 * 127.0.0.1:8000 — le navigateur allait chercher le fichier sur un autre
 * serveur, qui répondait 404, et la vidéo ne s'affichait pas alors qu'elle
 * était bien stockée.
 *
 * La configuration du disque produit désormais des URL relatives ; cette
 * migration répare les lignes déjà enregistrées. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('modules')
            ->where('contenu', 'like', 'http%/storage/%')
            ->orderBy('id')
            ->each(function ($module) {
                $chemin = parse_url($module->contenu, PHP_URL_PATH);

                if (! $chemin || ! str_starts_with($chemin, '/storage/')) {
                    return;
                }

                DB::table('modules')
                    ->where('id', $module->id)
                    ->update(['contenu' => $chemin]);
            });
    }

    public function down(): void
    {
        // Aucun retour en arrière : réintroduire un hôte codé en dur serait
        // précisément le défaut que cette migration corrige.
    }
};
