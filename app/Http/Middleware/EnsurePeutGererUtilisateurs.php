<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Autorise uniquement l'administrateur du site et les superviseurs à
 * atteindre la route protégée (gestion des comptes utilisateurs).
 */
class EnsurePeutGererUtilisateurs
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->peutGererUtilisateurs()) {
            abort(403, "Accès réservé à l'administrateur et aux superviseurs.");
        }

        return $next($request);
    }
}
