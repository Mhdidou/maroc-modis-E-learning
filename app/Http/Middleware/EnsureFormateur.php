<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Réserve l'atelier de construction de cours aux personnes susceptibles d'être
 * responsables d'une formation — formateurs et superviseurs — plus l'admin du
 * site, qui saisit pour leur compte. Un apprenant est refusé.
 *
 * Le superviseur est admis parce qu'une formation peut lui être attribuée :
 * sans cela, l'admin pourrait lui transmettre un contenu qu'il n'aurait aucun
 * moyen de modifier. Le périmètre exact reste borné par FormationPolicy, qui
 * limite chacun aux formations dont il est responsable.
 */
class EnsureFormateur
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        $autorise = $user && ($user->isFormateur() || $user->isSuperviseur() || $user->isAdmin());

        if (! $autorise) {
            abort(403, 'Accès réservé aux formateurs et superviseurs.');
        }

        return $next($request);
    }
}
