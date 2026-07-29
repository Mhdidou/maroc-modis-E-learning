<?php

namespace App\Http\Controllers\Builder;

use App\Http\Controllers\Controller;
use App\Http\Requests\Builder\FormationRequest;
use App\Models\Formation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Atelier de construction de cours (formateur). Le formateur ne voit et ne
 * modifie que ses formations ; l'admin les voit toutes. Toutes les écritures
 * invalident le cache de structure de la formation.
 */
class FormationBuilderController extends Controller
{
    /**
     * Liste des formations du formateur (toutes pour l'admin).
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $query = Formation::query()
            ->with('auteur:id,nom_complet,role')
            ->withCount('chapitres')
            ->orderByDesc('mis_a_jour_le')
            ->orderByDesc('id');

        if (! $user->isAdmin()) {
            $query->where('cree_par', $user->id);
        }

        return Inertia::render('Builder/Index', [
            'formations' => $query->paginate(12)->through(fn (Formation $f) => [
                'id' => $f->id,
                'titre' => $f->titre,
                'type' => $f->type,
                'statut' => $f->statut,
                'chapitres_count' => $f->chapitres_count,
                'responsable' => $f->auteur?->nom_complet,
                'attribuee' => $f->estAttribuee(),
            ]),
            // L'admin saisit pour le compte d'un tiers : il lui faut la liste
            // des responsables possibles dès le formulaire de création.
            'responsables' => $user->isAdmin() ? $this->responsablesPossibles() : null,
        ]);
    }

    /**
     * Crée une formation vide (brouillon) et ouvre son atelier.
     */
    public function store(FormationRequest $request): RedirectResponse
    {
        $user = $request->user();
        $donnees = $request->validated();

        // L'admin saisit toujours POUR quelqu'un : `cree_par` (le responsable,
        // dont le nom sera attribué à la formation et imprimé sur les
        // certificats) vient du formulaire, et on garde trace de l'opérateur
        // réel dans `saisi_par`. Un formateur est son propre responsable.
        $responsable = $user->isAdmin() ? (int) $donnees['cree_par'] : $user->id;
        unset($donnees['cree_par']);

        $formation = Formation::create($donnees + [
            'statut' => Formation::STATUT_BROUILLON,
            'cree_par' => $responsable,
            'saisi_par' => $responsable === $user->id ? null : $user->id,
        ]);

        return redirect()->route('builder.show', $formation)
            ->with('status', 'Formation créée. Ajoutez des chapitres et des modules.');
    }

    /**
     * Page atelier : arbre complet chapitres → modules (+ questions/checkpoints).
     */
    public function show(Request $request, Formation $formation): Response
    {
        $this->authorize('update', $formation);

        $formation->load([
            'auteur:id,nom_complet,role',
            'operateur:id,nom_complet',
            'chapitres' => fn ($q) => $q->orderBy('position'),
            'chapitres.modules' => fn ($q) => $q->orderBy('position'),
            'chapitres.modules.questions' => fn ($q) => $q->orderBy('id'),
            'chapitres.modules.checkpointsVideo' => fn ($q) => $q->orderBy('position_secondes'),
        ]);

        return Inertia::render('Builder/Edit', [
            'responsables' => $request->user()->isAdmin() ? $this->responsablesPossibles() : null,
            'formation' => [
                'id' => $formation->id,
                'titre' => $formation->titre,
                'description' => $formation->description,
                'type' => $formation->type,
                'statut' => $formation->statut,
                'validite_mois' => $formation->validite_mois,
                'cree_par' => $formation->cree_par,
                'responsable' => $formation->auteur?->nom_complet,
                'saisi_par' => $formation->operateur?->nom_complet,
                'attribuee' => $formation->estAttribuee(),
                'chapitres' => $formation->chapitres->map(fn ($c) => [
                    'id' => $c->id,
                    'titre' => $c->titre,
                    'position' => $c->position,
                    'modules' => $c->modules->map(fn ($m) => [
                        'id' => $m->id,
                        'type' => $m->type,
                        'titre' => $m->titre,
                        'contenu' => $m->contenu,
                        'consignes' => $m->consignes,
                        'position' => $m->position,
                        'nb_questions_tirees' => $m->nb_questions_tirees,
                        'seuil_reussite' => $m->seuil_reussite,
                        'duree_minutes' => $m->duree_minutes,
                        'questions' => $m->questions->map(fn ($q) => [
                            'id' => $q->id,
                            'enonce' => $q->enonce,
                            'bonne_reponse' => $q->bonne_reponse,
                            'mauvaises_reponses' => $q->mauvaises_reponses,
                        ]),
                        'checkpoints' => $m->checkpointsVideo->map(fn ($cp) => [
                            'id' => $cp->id,
                            'position_secondes' => $cp->position_secondes,
                            'enonce' => $cp->enonce,
                            'bonne_reponse' => $cp->bonne_reponse,
                            'mauvaises_reponses' => $cp->mauvaises_reponses,
                            'explication' => $cp->explication,
                        ]),
                    ]),
                ]),
            ],
        ]);
    }

    /**
     * Met à jour les métadonnées (titre, description, type, validité).
     */
    public function updateMeta(FormationRequest $request, Formation $formation): RedirectResponse
    {
        $this->authorize('update', $formation);

        $donnees = $request->validated();

        // Réattribution : réservée à l'admin (la règle `cree_par` n'existe dans
        // la Request que pour lui). Quand il transmet la formation à quelqu'un
        // d'autre, il redevient simple opérateur de la saisie.
        if ($request->user()->isAdmin() && isset($donnees['cree_par'])) {
            $donnees['saisi_par'] = (int) $donnees['cree_par'] === $request->user()->id
                ? null
                : $request->user()->id;
        } else {
            unset($donnees['cree_par']);
        }

        $formation->update($donnees);
        $this->oublierCache($formation);

        return back()->with('status', 'Formation mise à jour.');
    }

    /**
     * Publie la formation (contrôle qu'elle contient au moins un module).
     */
    public function publier(Request $request, Formation $formation): RedirectResponse
    {
        $this->authorize('update', $formation);

        $aDesModules = $formation->modules()->exists();
        if (! $aDesModules) {
            throw new HttpException(422, 'Ajoutez au moins un module avant de publier.');
        }

        // Filet de sécurité : une formation encore rattachée à l'admin n'est
        // attribuée à personne. La publier ferait figurer l'administrateur du
        // site comme formateur sur les certificats délivrés.
        if (! $formation->estAttribuee()) {
            throw new HttpException(
                422,
                'Attribuez cette formation à un formateur ou à un superviseur avant de la publier.'
            );
        }

        $formation->update(['statut' => Formation::STATUT_PUBLIE]);
        $this->oublierCache($formation);

        return back()->with('status', 'Formation publiée.');
    }

    /**
     * Repasse la formation en brouillon.
     */
    public function depublier(Request $request, Formation $formation): RedirectResponse
    {
        $this->authorize('update', $formation);

        $formation->update(['statut' => Formation::STATUT_BROUILLON]);
        $this->oublierCache($formation);

        return back()->with('status', 'Formation repassée en brouillon.');
    }

    public function destroy(Request $request, Formation $formation): RedirectResponse
    {
        $this->authorize('delete', $formation);

        // Une formation qui a déjà servi ne se supprime plus : les FK de
        // `certificats` et `inscriptions` sont en RESTRICT, on refuse donc en
        // amont avec un message utile plutôt que de laisser remonter une erreur
        // SQL. Retirer du catalogue = dépublier, ce qui préserve les certificats
        // déjà délivrés et la progression des apprenants en cours.
        if ($formation->porteHistorique()) {
            return back()->withErrors([
                'suppression' => 'Cette formation a déjà été suivie ou certifiée : '
                    .'elle ne peut plus être supprimée sans effacer un historique '
                    .'de formation. Dépubliez-la pour la retirer du catalogue.',
            ]);
        }

        $this->oublierCache($formation);
        $formation->delete();

        return redirect()->route('builder.index')
            ->with('status', 'Formation supprimée.');
    }

    /**
     * Personnes à qui une formation peut être attribuée : formateurs et
     * superviseurs actifs. L'admin en est volontairement exclu — il opère,
     * il n'est pas responsable pédagogique.
     *
     * @return Collection<int, array{id: int, nom: string, role: string}>
     */
    private function responsablesPossibles()
    {
        return User::whereIn('role', [User::ROLE_FORMATEUR, User::ROLE_SUPERVISEUR])
            ->orderBy('role')
            ->orderBy('nom_complet')
            ->get(['id', 'nom_complet', 'role'])
            ->map(fn (User $u) => [
                'id' => $u->id,
                'nom' => $u->nom_complet,
                'role' => $u->role,
            ]);
    }

    /**
     * Invalide le cache de structure (partagé avec le lecteur apprenant).
     */
    private function oublierCache(Formation $formation): void
    {
        Cache::forget("formation.structure.{$formation->id}");
    }
}
