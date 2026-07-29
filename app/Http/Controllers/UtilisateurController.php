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
        // `withTrashed()` : les comptes désactivés restent visibles ici, sinon un
        // gestionnaire n'aurait aucun moyen de réactiver un employé de retour et
        // recréerait un doublon, scindant son historique de formation.
        $utilisateurs = User::withTrashed()
            ->with('superviseur')
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
                'actif' => $u->estActif(),
                'desactive_le' => optional($u->supprime_le)->format('d/m/Y'),
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

    /**
     * Désactive un compte (départ d'un employé du plateau).
     *
     * Soft delete volontaire : l'employé perd immédiatement l'accès (le global
     * scope l'exclut de l'authentification) mais ses inscriptions, sa
     * progression et ses certificats restent intacts et consultables en audit.
     * Il n'existe aucune suppression physique d'un compte dans l'application.
     */
    public function destroy(Request $request, User $utilisateur): RedirectResponse
    {
        $this->autoriseGestionDe($request, $utilisateur);

        // Se désactiver soi-même verrouillerait le compte du gestionnaire.
        abort_if($utilisateur->is($request->user()), 403, 'Vous ne pouvez pas désactiver votre propre compte.');

        $utilisateur->delete();

        return redirect()->route('utilisateurs.index')
            ->with('status', "Le compte de {$utilisateur->nom_complet} a été désactivé. "
                .'Son historique de formation est conservé.');
    }

    /**
     * Réactive un compte désactivé : l'employé retrouve son accès et son
     * historique de formation d'origine.
     */
    public function restore(Request $request, int $utilisateur): RedirectResponse
    {
        $cible = User::withTrashed()->findOrFail($utilisateur);

        $this->autoriseGestionDe($request, $cible);

        $cible->restore();

        return redirect()->route('utilisateurs.index')
            ->with('status', "Le compte de {$cible->nom_complet} a été réactivé.");
    }

    /**
     * Un gestionnaire ne peut agir que sur les rôles qu'il a le droit de gérer
     * (un superviseur ne touche ni aux admins ni aux autres superviseurs).
     */
    private function autoriseGestionDe(Request $request, User $cible): void
    {
        abort_unless(
            in_array($cible->role, $request->user()->rolesGerables(), true),
            403,
            "Vous n'avez pas le droit de gérer ce compte."
        );
    }
}
