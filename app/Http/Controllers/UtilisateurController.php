<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Gestion des comptes de la plateforme.
 * Accès réservé à l'administrateur du site et aux superviseurs
 * (voir middleware `peut.gerer.utilisateurs`).
 */
class UtilisateurController extends Controller
{
    /**
     * Liste des comptes gérables par l'utilisateur courant.
     * L'admin voit aussi les superviseurs (rôle supérieur) ; un superviseur
     * ne voit que les formateurs et apprenants.
     */
    public function index(Request $request): Response
    {
        $utilisateurs = User::with('superviseur')
            ->whereIn('role', $request->user()->rolesGerables())
            ->orderBy('role')
            ->orderBy('nom_complet')
            ->get()
            ->map(fn ($u) => [
                'id' => $u->id,
                'nom' => $u->nom_complet,
                'email' => $u->email,
                'role' => $u->role,
                'domaine' => $u->domaine,
                'superviseur' => $u->superviseur?->nom_complet,
                'cree_le' => optional($u->cree_le)->format('d/m/Y'),
            ]);

        return Inertia::render('Utilisateurs/Index', [
            'utilisateurs' => $utilisateurs,
        ]);
    }

    /**
     * Formulaire de création d'un nouveau compte.
     * Les rôles proposés dépendent de la hiérarchie (admin > superviseur).
     */
    public function create(Request $request): Response
    {
        return Inertia::render('Utilisateurs/Create', [
            'rolesDisponibles' => $request->user()->rolesGerables(),
            'superviseurs' => User::where('role', User::ROLE_SUPERVISEUR)
                ->orderBy('nom_complet')
                ->get(['id', 'nom_complet']),
        ]);
    }

    /**
     * Enregistre le nouveau compte.
     */
    public function store(Request $request): RedirectResponse
    {
        // Un superviseur ne peut jamais créer d'admin ni de superviseur.
        $rolesAutorises = $request->user()->rolesGerables();

        $validated = $request->validate([
            'nom_complet' => 'required|string|max:150',
            'email' => 'required|string|lowercase|email|max:150|unique:utilisateurs,email',
            'role' => ['required', Rule::in($rolesAutorises)],
            'domaine' => 'nullable|string|max:100',
            'superviseur_id' => 'nullable|exists:utilisateurs,id',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        User::create([
            'nom_complet' => $validated['nom_complet'],
            'email' => $validated['email'],
            'mot_de_passe' => $validated['password'],
            'role' => $validated['role'],
            'domaine' => $validated['domaine'] ?? null,
            // Le lien superviseur ne concerne que les apprenants.
            'superviseur_id' => $validated['role'] === User::ROLE_APPRENANT
                ? ($validated['superviseur_id'] ?? null)
                : null,
        ]);

        return redirect()->route('utilisateurs.index')
            ->with('status', 'Le compte a été créé avec succès.');
    }
}
