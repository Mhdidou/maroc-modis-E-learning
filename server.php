<?php

/**
 * Routeur du serveur de développement (`php artisan serve`).
 *
 * Reprend le routeur fourni par Laravel — émuler mod_rewrite — en ajoutant le
 * support des requêtes HTTP `Range`, que le serveur intégré de PHP ne gère pas.
 *
 * POURQUOI C'EST NÉCESSAIRE
 * Pour se déplacer dans une vidéo, le navigateur demande un fragment d'octets
 * (`Range: bytes=...`) et attend un `206 Partial Content`. Le serveur intégré
 * renvoie systématiquement `200` avec le fichier entier : le navigateur en
 * conclut que le média n'est pas « seekable », `video.seekable` reste vide et
 * le lecteur n'affiche plus qu'un bouton lecture/pause — impossible d'avancer
 * ou de revenir en arrière, quelle que soit la configuration du lecteur.
 *
 * En production (Apache, nginx) les fichiers statiques sont servis par le
 * serveur web, qui gère `Range` nativement : ce fichier n'a alors aucun effet.
 */
$publicPath = getcwd();

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? ''
);

$cheminFichier = $publicPath.$uri;

if ($uri !== '/' && file_exists($cheminFichier) && ! is_dir($cheminFichier)) {
    // Seuls les médias volumineux ont besoin d'être servis par fragments ; le
    // reste (CSS, JS, images) part par le chemin normal, plus rapide.
    $extension = strtolower(pathinfo($cheminFichier, PATHINFO_EXTENSION));
    $typesParExtension = [
        'mp4' => 'video/mp4',
        'webm' => 'video/webm',
        'ogg' => 'video/ogg',
        'ogv' => 'video/ogg',
        'mov' => 'video/quicktime',
        'm4v' => 'video/x-m4v',
        'mp3' => 'audio/mpeg',
        'wav' => 'audio/wav',
        'pdf' => 'application/pdf',
    ];

    if (! isset($typesParExtension[$extension])) {
        return false; // laisser le serveur intégré s'en charger
    }

    $taille = filesize($cheminFichier);
    $debut = 0;
    $fin = $taille - 1;
    $partiel = false;

    $enTeteRange = $_SERVER['HTTP_RANGE'] ?? null;

    if ($enTeteRange !== null && preg_match('/bytes=(\d*)-(\d*)/i', $enTeteRange, $m)) {
        $debutDemande = $m[1] === '' ? null : (int) $m[1];
        $finDemandee = $m[2] === '' ? null : (int) $m[2];

        if ($debutDemande === null && $finDemandee !== null) {
            // Forme « bytes=-500 » : les 500 derniers octets.
            $debut = max(0, $taille - $finDemandee);
        } else {
            $debut = $debutDemande ?? 0;
            $fin = $finDemandee ?? $fin;
        }

        $fin = min($fin, $taille - 1);

        if ($debut > $fin || $debut >= $taille) {
            header('HTTP/1.1 416 Range Not Satisfiable');
            header("Content-Range: bytes */$taille");

            return true;
        }

        $partiel = true;
    }

    $longueur = $fin - $debut + 1;

    header($partiel ? 'HTTP/1.1 206 Partial Content' : 'HTTP/1.1 200 OK');
    header('Content-Type: '.$typesParExtension[$extension]);
    header('Accept-Ranges: bytes');
    header('Content-Length: '.$longueur);

    if ($partiel) {
        header("Content-Range: bytes $debut-$fin/$taille");
    }

    $flux = fopen($cheminFichier, 'rb');
    fseek($flux, $debut);

    // Envoi par blocs : une vidéo de 500 Mo ne doit jamais tenir en mémoire.
    $restant = $longueur;
    while ($restant > 0 && ! feof($flux)) {
        $morceau = fread($flux, (int) min(8192, $restant));

        if ($morceau === false) {
            break;
        }

        echo $morceau;
        flush();
        $restant -= strlen($morceau);
    }

    fclose($flux);

    return true;
}

$formattedDateTime = date('D M j H:i:s Y');

$requestMethod = $_SERVER['REQUEST_METHOD'];
$remoteAddress = $_SERVER['REMOTE_ADDR'].':'.$_SERVER['REMOTE_PORT'];

file_put_contents('php://stdout', "[$formattedDateTime] $remoteAddress [$requestMethod] URI: $uri\n");

require_once $publicPath.'/index.php';
