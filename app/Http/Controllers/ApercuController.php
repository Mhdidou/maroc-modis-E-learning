<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\SuiviApprenant;
use App\Models\Certificat;
use App\Models\Formation;
use App\Models\Inscription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Aperçu administrateur : l'admin visualise chaque espace (Apprenant, Formateur,
 * Superviseur) avec des données globales/représentatives et un bandeau « Mode
 * aperçu ». Aucune usurpation d'identité — vue générique en lecture.
 * Réservé à l'administrateur du site.
 */
class ApercuController extends Controller
{
    use SuiviApprenant;

    /**
     * Aperçu de l'espace Apprenant : catalogue des formations et totaux plateforme.
     */
    public function apprenant(Request $request): Response
    {
        $this->autoriserAdmin($request);

        $formations = Formation::withCount('modules')
            ->orderBy('titre')
            ->get()
            ->map(fn (Formation $f) => [
                'id' => $f->id,
                // Pas de vraie inscription en mode aperçu : on réutilise l'id de
                // formation comme clé de liste stable côté React.
                'inscription_id' => $f->id,
                'titre' => $f->titre,
                'type' => $f->type,
                'statut' => 'non_commencee',
                'objectif_quotidien' => 3,
                'modules_total' => $f->modules_count,
                'modules_faits' => 0,
                'progression' => 0,
            ])
            ->values();

        return Inertia::render('Espace/Apprenant', [
            'apercu' => 'apprenant',
            'stats' => [
                'inscriptions' => Inscription::count(),
                'en_cours' => Inscription::where('statut', 'en_cours')->count(),
                'terminees' => Inscription::where('statut', 'terminee')->count(),
                'certificats' => Certificat::valides()->count(),
            ],
            'formations' => $formations,
            'objectifDuJour' => ['objectif' => 0, 'faitAujourdhui' => 0, 'restantes' => 0],
            'activiteSemaine' => $this->activiteSemaine($request->user()),
            'certificats' => Certificat::with('formation:id,titre')
                ->orderByDesc('delivre_le')
                ->limit(5)
                ->get()
                ->map(fn (Certificat $c) => [
                    'id' => $c->id,
                    'numero_unique' => $c->numero_unique,
                    'titre' => $c->formation->titre ?? 'Formation',
                    'delivre_le' => optional($c->delivre_le)->format('d/m/Y'),
                    'expire_le' => optional($c->expire_le)->format('d/m/Y'),
                    'statut' => $c->statut(),
                ])
                ->values(),
        ]);
    }

    /**
     * Aperçu de l'espace Formateur : toutes les formations de la plateforme.
     */
    public function formateur(Request $request): Response
    {
        $this->autoriserAdmin($request);

        $formations = Formation::withCount(['modules', 'inscriptions'])->get();

        return Inertia::render('Espace/Formateur', [
            'apercu' => 'formateur',
            'stats' => [
                'formations' => $formations->count(),
                'modules' => $formations->sum('modules_count'),
                'inscrits' => $formations->sum('inscriptions_count'),
                'certifiantes' => $formations->where('type', 'certifiante')->count(),
            ],
            'formations' => $formations->map(fn (Formation $f) => [
                'id' => $f->id,
                'titre' => $f->titre,
                'type' => $f->type,
                'modules' => $f->modules_count,
                'inscrits' => $f->inscriptions_count,
            ])->values(),
        ]);
    }

    /**
     * Aperçu de l'espace Superviseur : tous les apprenants de la plateforme.
     */
    public function superviseur(Request $request): Response
    {
        $this->autoriserAdmin($request);

        $apprenants = User::where('role', User::ROLE_APPRENANT)
            ->orderBy('nom_complet')
            ->get();

        return Inertia::render('Espace/Superviseur', [
            'apercu' => 'superviseur',
            'stats' => [
                'apprenants' => $apprenants->count(),
                'formateurs' => User::where('role', User::ROLE_FORMATEUR)->count(),
                'formations' => Formation::count(),
                'certificats' => Certificat::valides()->count(),
                'certificats_expires' => Certificat::expires()->count(),
            ],
            'repartitionStatuts' => Inscription::select('statut', DB::raw('COUNT(*) as total'))
                ->groupBy('statut')
                ->pluck('total', 'statut'),
            'apprenants' => $apprenants->map(fn (User $a) => [
                'id' => $a->id,
                'nom' => $a->nom_complet,
                'email' => $a->email,
                'domaine' => $a->domaine,
            ])->values(),
        ]);
    }

    /**
     * L'aperçu est strictement réservé à l'administrateur du site.
     */
    private function autoriserAdmin(Request $request): void
    {
        abort_unless($request->user()->isAdmin(), 403, "Aperçu réservé à l'administrateur du site.");
    }
}
