<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Crée un compte de démonstration pour chacun des quatre acteurs.
     * Mot de passe commun : « password » (à changer en production).
     */
    public function run(): void
    {
        $motDePasse = 'password';

        User::updateOrCreate(
            ['email' => 'admin@triumph.com'],
            [
                'nom_complet' => 'Administrateur du site',
                'mot_de_passe' => $motDePasse,
                'role' => User::ROLE_ADMIN,
            ]
        );

        $superviseur = User::updateOrCreate(
            ['email' => 'superviseur@triumph.com'],
            [
                'nom_complet' => 'Nadia Superviseur',
                'mot_de_passe' => $motDePasse,
                'role' => User::ROLE_SUPERVISEUR,
                'domaine' => 'Couture',
            ]
        );

        User::updateOrCreate(
            ['email' => 'formateur@triumph.com'],
            [
                'nom_complet' => 'Karim Formateur',
                'mot_de_passe' => $motDePasse,
                'role' => User::ROLE_FORMATEUR,
                'domaine' => 'Couture',
            ]
        );

        User::updateOrCreate(
            ['email' => 'apprenant@triumph.com'],
            [
                'nom_complet' => 'Fatima Apprenante',
                'mot_de_passe' => $motDePasse,
                'role' => User::ROLE_APPRENANT,
                'domaine' => 'Couture',
                'superviseur_id' => $superviseur->id,
            ]
        );

        User::updateOrCreate(
            ['email' => 'apprenant2@triumph.com'],
            [
                'nom_complet' => 'Youssef Apprenant',
                'mot_de_passe' => $motDePasse,
                'role' => User::ROLE_APPRENANT,
                'domaine' => 'Coupe',
                'superviseur_id' => $superviseur->id,
            ]
        );
    }
}
