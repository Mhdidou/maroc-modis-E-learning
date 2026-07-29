<?php

namespace App\Http\Controllers;

use App\Models\Formation;
use App\Models\Inscription;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Affectation ciblée : le superviseur (ou l'admin) inscrit un apprenant précis
 * à une formation précise, en fixant un objectif quotidien (leçons/jour).
 * Accès réservé à l'admin et aux superviseurs (middleware `peut.gerer.utilisateurs`).
 */
class AffectationController extends Controller
{
    /**
     * Formulaire d'affectation.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('Affectations/Create', [
            'apprenants' => $this->apprenantsGerables($request->user())
                ->map(fn (User $a) => [
                    'id' => $a->id,
                    'nom' => $a->nom_complet,
                    'domaine' => $a->domaine,
                ])
                ->values(),
            'formations' => Formation::orderBy('titre')
                ->get(['id', 'titre', 'type'])
                ->map(fn (Formation $f) => [
                    'id' => $f->id,
                    'titre' => $f->titre,
                    'type' => $f->type,
                ])
                ->values(),
        ]);
    }

    /**
     * Enregistre l'affectation (crée l'inscription).
     */
    public function store(Request $request): RedirectResponse
    {
        // L'apprenant visé doit faire partie du périmètre gérable.
        $apprenantsGerables = $this->apprenantsGerables($request->user())->pluck('id');

        $validated = $request->validate([
            'utilisateur_id' => ['required', Rule::in($apprenantsGerables)],
            'formation_id' => ['required', 'exists:formations,id'],
            'objectif_quotidien' => ['required', 'integer', 'min:1', 'max:20'],
        ], [
            'utilisateur_id.in' => "Cet apprenant n'est pas rattaché à votre périmètre.",
        ]);

        // Une même formation ne peut être affectée deux fois au même apprenant
        // (contrainte d'unicité en base).
        $dejaInscrit = Inscription::where('utilisateur_id', $validated['utilisateur_id'])
            ->where('formation_id', $validated['formation_id'])
            ->exists();

        if ($dejaInscrit) {
            throw ValidationException::withMessages([
                'formation_id' => 'Cet apprenant est déjà inscrit à cette formation.',
            ]);
        }

        Inscription::create([
            'utilisateur_id' => $validated['utilisateur_id'],
            'formation_id' => $validated['formation_id'],
            'statut' => 'non_commencee',
            'objectif_quotidien' => $validated['objectif_quotidien'],
            'inscrit_le' => now(),
        ]);

        return redirect()->route('dashboard')
            ->with('status', 'La formation a été affectée à l\'apprenant.');
    }

    /**
     * Apprenants que l'utilisateur courant peut affecter.
     * L'admin voit tous les apprenants ; un superviseur, uniquement les siens.
     */
    private function apprenantsGerables(User $user)
    {
        $query = User::where('role', User::ROLE_APPRENANT)->orderBy('nom_complet');

        if (! $user->isAdmin()) {
            $query->where('superviseur_id', $user->id);
        }

        return $query->get();
    }
}
