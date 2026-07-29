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
 * Aiguille chaque utilisateur vers l'espace correspondant à son rôle
 * et fournit les statistiques métier affichées sur son tableau de bord.
 */
class DashboardController extends Controller
{
    use SuiviApprenant;

    public function index(Request $request): Response
    {
        $user = $request->user();

        return match ($user->role) {
            User::ROLE_APPRENANT => $this->apprenant($user),
            User::ROLE_FORMATEUR => $this->formateur($user),
            User::ROLE_ADMIN => $this->admin($user),
            default => $this->superviseur($user),
        };
    }

    /**
     * Espace Administration — vue globale de la plateforme.
     * L'admin est le rôle supérieur : il supervise notamment les superviseurs.
     */
    private function admin(User $user): Response
    {
        $superviseurs = User::where('role', User::ROLE_SUPERVISEUR)
            ->withCount('apprenants')
            ->orderBy('nom_complet')
            ->get();

        return Inertia::render('Espace/Admin', [
            'stats' => [
                'superviseurs' => $superviseurs->count(),
                'formateurs' => User::where('role', User::ROLE_FORMATEUR)->count(),
                'apprenants' => User::where('role', User::ROLE_APPRENANT)->count(),
                'formations' => Formation::count(),
                // Un certificat expiré n'est plus une certification acquise :
                // les deux compteurs sont distincts, jamais confondus.
                'certificats' => Certificat::valides()->count(),
                'certificats_expires' => Certificat::expires()->count(),
            ],
            'repartitionStatuts' => Inscription::select('statut', DB::raw('COUNT(*) as total'))
                ->groupBy('statut')
                ->pluck('total', 'statut'),
            'superviseurs' => $superviseurs->map(fn ($s) => [
                'id' => $s->id,
                'nom' => $s->nom_complet,
                'email' => $s->email,
                'domaine' => $s->domaine,
                'apprenants' => $s->apprenants_count,
            ])->values(),
        ]);
    }

    private function apprenant(User $user): Response
    {
        $suivi = $this->suiviFormations($user);
        $formations = collect($suivi['formations']);

        return Inertia::render('Espace/Apprenant', [
            'stats' => [
                'inscriptions' => $formations->count(),
                'en_cours' => $formations->where('statut', 'en_cours')->count(),
                'terminees' => $formations->where('statut', 'terminee')->count(),
                'certificats' => Certificat::valides()
                    ->where('utilisateur_id', $user->id)
                    ->count(),
            ],
            'formations' => $suivi['formations'],
            'objectifDuJour' => $suivi['objectifDuJour'],
            'activiteSemaine' => $this->activiteSemaine($user),
            'certificats' => $this->certificatsApprenant($user),
        ]);
    }

    private function formateur(User $user): Response
    {
        $formations = Formation::withCount(['modules', 'inscriptions'])
            ->where('cree_par', $user->id)
            ->get();

        return Inertia::render('Espace/Formateur', [
            'stats' => [
                'formations' => $formations->count(),
                'modules' => $formations->sum('modules_count'),
                'inscrits' => $formations->sum('inscriptions_count'),
                'certifiantes' => $formations->where('type', 'certifiante')->count(),
            ],
            'formations' => $formations->map(fn ($f) => [
                'id' => $f->id,
                'titre' => $f->titre,
                'type' => $f->type,
                'modules' => $f->modules_count,
                'inscrits' => $f->inscriptions_count,
            ])->values(),
        ]);
    }

    private function superviseur(User $user): Response
    {
        // Un superviseur ne pilote que les apprenants qui lui sont rattachés.
        $apprenants = User::where('role', User::ROLE_APPRENANT)
            ->where('superviseur_id', $user->id)
            ->get();

        return Inertia::render('Espace/Superviseur', [
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
            'apprenants' => $apprenants->map(fn ($a) => [
                'id' => $a->id,
                'nom' => $a->nom_complet,
                'email' => $a->email,
                'domaine' => $a->domaine,
            ])->values(),
        ]);
    }
}
