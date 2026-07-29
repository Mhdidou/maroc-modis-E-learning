<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            'flash' => [
                'status' => fn () => $request->session()->get('status'),
            ],
            // Limite d'import réellement appliquée par PHP. Partagée pour que le
            // front refuse un fichier trop lourd AVANT de l'envoyer : passé
            // `post_max_size`, PHP vide la requête et l'erreur devient
            // difficile à restituer. Lue dans php.ini pour ne jamais diverger
            // d'une valeur codée en dur côté client.
            'limites' => [
                'upload_octets' => $this->limiteUploadOctets(),
            ],
        ];
    }

    /**
     * Plus petite des deux limites PHP qui plafonnent un import, en octets :
     * un fichier passe seulement s'il tient sous les deux.
     */
    private function limiteUploadOctets(): int
    {
        $valeurs = array_filter([
            $this->enOctets((string) ini_get('upload_max_filesize')),
            $this->enOctets((string) ini_get('post_max_size')),
        ]);

        return $valeurs === [] ? 0 : (int) min($valeurs);
    }

    /**
     * Convertit une taille php.ini (« 512M », « 8G », « 1024K ») en octets.
     */
    private function enOctets(string $taille): int
    {
        $taille = trim($taille);

        if ($taille === '') {
            return 0;
        }

        $nombre = (int) $taille;

        return match (strtolower(substr($taille, -1))) {
            'g' => $nombre * 1024 ** 3,
            'm' => $nombre * 1024 ** 2,
            'k' => $nombre * 1024,
            default => $nombre,
        };
    }
}
