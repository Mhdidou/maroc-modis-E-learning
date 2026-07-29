<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Certificat;
use App\Models\Inscription;
use App\Models\JournalConnexion;
use App\Models\Module;
use App\Models\Progression;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Calculs de suivi partagés par le tableau de bord apprenant et la page
 * « Mes formations » : progression en %, objectif du jour, activité de la
 * semaine et certificats. Requêtes agrégées pour éviter le N+1.
 */
trait SuiviApprenant
{
    /**
     * Formations attribuées à l'apprenant enrichies de leur progression.
     *
     * @return array{formations: array<int, array<string, mixed>>, objectifDuJour: array<string, int>}
     */
    protected function suiviFormations(User $user): array
    {
        $inscriptions = Inscription::with('formation:id,titre,type')
            ->where('utilisateur_id', $user->id)
            ->get();

        // Nombre de modules par formation (dénominateur de la progression).
        // Les modules dépendent désormais des chapitres : on agrège via une jointure.
        $modulesParFormation = Module::query()
            ->join('chapitres', 'modules.chapitre_id', '=', 'chapitres.id')
            ->whereIn('chapitres.formation_id', $inscriptions->pluck('formation_id'))
            ->select('chapitres.formation_id', DB::raw('COUNT(*) as total'))
            ->groupBy('chapitres.formation_id')
            ->pluck('total', 'formation_id');

        // Modules terminés par inscription (numérateur).
        $faitsParInscription = Progression::whereIn('inscription_id', $inscriptions->pluck('id'))
            ->where('statut', 'terminee')
            ->select('inscription_id', DB::raw('COUNT(*) as total'))
            ->groupBy('inscription_id')
            ->pluck('total', 'inscription_id');

        $formations = $inscriptions->map(function (Inscription $i) use ($modulesParFormation, $faitsParInscription) {
            $total = (int) ($modulesParFormation[$i->formation_id] ?? 0);
            $faits = (int) ($faitsParInscription[$i->id] ?? 0);

            return [
                'id' => $i->formation_id,
                'inscription_id' => $i->id,
                'titre' => $i->formation->titre ?? 'Formation',
                'type' => $i->formation->type ?? 'non_certifiante',
                'statut' => $i->statut,
                'objectif_quotidien' => (int) $i->objectif_quotidien,
                'modules_total' => $total,
                'modules_faits' => min($faits, $total),
                'progression' => $i->pourcentageProgression($total, $faits),
            ];
        })->values()->all();

        // Objectif du jour : somme des objectifs des formations encore actives,
        // comparée aux modules réellement terminés aujourd'hui.
        $objectif = $inscriptions
            ->where('statut', '!=', 'terminee')
            ->sum('objectif_quotidien');

        $faitAujourdhui = Progression::whereIn('inscription_id', $inscriptions->pluck('id'))
            ->where('statut', 'terminee')
            ->whereDate('termine_le', today())
            ->count();

        return [
            'formations' => $formations,
            'objectifDuJour' => [
                'objectif' => (int) $objectif,
                'faitAujourdhui' => (int) $faitAujourdhui,
                'restantes' => (int) max(0, $objectif - $faitAujourdhui),
            ],
        ];
    }

    /**
     * Activité de la semaine courante (lundi → dimanche) : quels jours
     * l'utilisateur s'est connecté.
     *
     * @return array{jours: array<int, array{date: string, court: string, actif: bool}>, actifs: int}
     */
    protected function activiteSemaine(User $user): array
    {
        $debut = Carbon::now()->startOfWeek(Carbon::MONDAY);
        $fin = (clone $debut)->endOfWeek(Carbon::SUNDAY);

        $joursActifs = JournalConnexion::where('utilisateur_id', $user->id)
            ->whereBetween('jour', [$debut->toDateString(), $fin->toDateString()])
            ->pluck('jour')
            ->map(fn ($j) => Carbon::parse($j)->toDateString())
            ->all();

        $courts = ['L', 'M', 'M', 'J', 'V', 'S', 'D'];
        $jours = [];

        for ($n = 0; $n < 7; $n++) {
            $date = (clone $debut)->addDays($n);
            $jours[] = [
                'date' => $date->toDateString(),
                'court' => $courts[$n],
                'actif' => in_array($date->toDateString(), $joursActifs, true),
            ];
        }

        return [
            'jours' => $jours,
            'actifs' => count(array_unique($joursActifs)),
        ];
    }

    /**
     * Certificats obtenus par l'apprenant (avec le titre de la formation).
     *
     * Expose la validité : sans `expire_le` ni `statut`, un certificat périmé
     * était indiscernable d'un certificat valable dans la liste.
     *
     * @return array<int, array{id: int, numero_unique: string, titre: string,
     *     delivre_le: string|null, expire_le: string|null, statut: string}>
     */
    protected function certificatsApprenant(User $user): array
    {
        return Certificat::with('formation:id,titre')
            ->where('utilisateur_id', $user->id)
            ->orderByDesc('delivre_le')
            ->get()
            ->map(fn (Certificat $c) => [
                'id' => $c->id,
                'numero_unique' => $c->numero_unique,
                'titre' => $c->formation->titre ?? 'Formation',
                'delivre_le' => optional($c->delivre_le)->format('d/m/Y'),
                'expire_le' => optional($c->expire_le)->format('d/m/Y'),
                'statut' => $c->statut(),
            ])
            ->values()
            ->all();
    }
}
