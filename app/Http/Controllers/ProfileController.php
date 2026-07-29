<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'status' => session('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Le front-end envoie `name` (contrat Breeze conservé) alors que la
        // colonne s'appelle `nom_complet` : `name` n'étant pas fillable, un
        // fill() direct l'abandonnait silencieusement et le nom n'était jamais
        // enregistré. Le schéma n'a par ailleurs pas de `email_verified_at` —
        // le reset de vérification hérité de Breeze est retiré.
        $request->user()->fill([
            'nom_complet' => $validated['name'],
            'email' => $validated['email'],
        ]);

        $request->user()->save();

        return Redirect::route('profile.edit');
    }

    /*
     * Pas de suppression de compte par l'utilisateur lui-même : les inscriptions
     * et les certificats sont des pièces d'audit et un employé ne peut pas
     * effacer son propre historique de formation. L'offboarding passe par la
     * désactivation, réservée à l'admin et aux superviseurs
     * (UtilisateurController::destroy).
     */
}
