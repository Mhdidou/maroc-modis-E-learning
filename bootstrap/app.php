<?php

use App\Http\Middleware\EnsureFormateur;
use App\Http\Middleware\EnsurePeutGererUtilisateurs;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        // Alias : réserve la gestion des comptes à l'admin + superviseurs.
        $middleware->alias([
            'peut.gerer.utilisateurs' => EnsurePeutGererUtilisateurs::class,
            'est.formateur' => EnsureFormateur::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Réponse JSON uniforme {message, errors} pour toute requête d'API ou
        // attendant du JSON (endpoints de validation du moteur pédagogique).
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Fichier plus lourd que `post_max_size` : PHP vide le corps de la
        // requête AVANT que Laravel ne s'exécute.
        //
        // Surtout, `ValidatePostSize` est un middleware GLOBAL qui lève cette
        // exception avant `StartSession` : rediriger avec `withErrors()` flashe
        // dans une session jamais démarrée ni enregistrée, donc l'erreur
        // disparaît en silence et l'utilisateur croit son import réussi. On
        // renvoie donc une vraie réponse 413, sans dépendre de la session.
        $exceptions->render(function (PostTooLargeException $e, Request $request) {
            $limite = ini_get('upload_max_filesize') ?: 'la limite du serveur';

            $message = "Fichier trop volumineux : le serveur n'accepte pas plus de {$limite}. "
                .'Si cette limite paraît trop basse, redémarrez le serveur web après '
                .'modification de php.ini — PHP ne relit sa configuration qu’au démarrage.';

            // Inertia et XHR envoient X-Requested-With / X-Inertia ; les
            // en-têtes survivent à la troncature du corps.
            if ($request->expectsJson() || $request->hasHeader('X-Inertia') || $request->ajax()) {
                return response()->json([
                    'message' => $message,
                    'errors' => ['fichier' => [$message]],
                ], 413);
            }

            return response($message, 413);
        });
    })->create();
