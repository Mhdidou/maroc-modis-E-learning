<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        // Colonne `mot_de_passe`, hachée par le cast `hashed` du modèle : écrire
        // `password` provoquait une erreur SQL (colonne inexistante).
        $request->user()->update([
            'mot_de_passe' => $validated['password'],
        ]);

        return back();
    }
}
