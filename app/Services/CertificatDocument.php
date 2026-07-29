<?php

namespace App\Services;

use App\Models\Certificat;
use BaconQrCode\Renderer\Color\Rgb;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\Fill;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Cache;

/**
 * Source unique du document « certificat ».
 *
 * Le certificat n'existe qu'à un seul endroit : la vue `certificats.modele`,
 * rendue en HTML autonome (CSS en ligne, logo et QR en data-URI, aucune requête
 * externe). Ce même artefact est servi trois fois :
 *
 *   - à l'écran      → injecté dans une iframe par CertificatController@show ;
 *   - au format PDF  → passé à dompdf pour le téléchargement ;
 *   - hors ligne     → écrit sur disque par le job GenererCertificat.
 *
 * Ce qui s'affiche, ce qui s'imprime et ce qui se télécharge sont donc
 * identiques par construction : les deux gabarits ne peuvent plus diverger.
 */
class CertificatDocument
{
    /**
     * Données d'affichage du document. Toutes les valeurs sont prêtes à
     * l'emploi : la vue ne fait aucun calcul et n'interroge pas la base.
     *
     * @return array<string, mixed>
     */
    public function donnees(Certificat $certificat): array
    {
        $certificat->loadMissing([
            'utilisateur:id,nom_complet,domaine',
            'formation:id,titre,type,cree_par',
            'formation.auteur:id,nom_complet',
        ]);

        $urlVerification = route('certificats.verifier', $certificat->numero_unique);

        return [
            'numero' => $certificat->numero_unique,
            'apprenant' => $certificat->utilisateur->nom_complet ?? '',
            'domaine' => $certificat->utilisateur->domaine,
            'formation' => $certificat->formation->titre ?? 'Formation',
            'certifiante' => (bool) $certificat->formation?->isCertifiante(),
            'nb_modules' => $this->nombreModules($certificat),
            'formateur' => $certificat->formation->auteur->nom_complet ?? null,
            'delivre_le' => $this->dateLongue($certificat->delivre_le),
            'expire_le' => $this->dateLongue($certificat->expire_le),
            'est_expire' => $certificat->estExpire(),
            'url_verification' => $urlVerification,
            'logo' => $this->logoDataUri(),
            'qr' => $this->qrDataUri($urlVerification),
        ];
    }

    /**
     * Document HTML autonome — utilisable tel quel dans une iframe, un fichier
     * `.html` ou dompdf.
     */
    public function html(Certificat $certificat): string
    {
        return view('certificats.modele', $this->donnees($certificat))->render();
    }

    /**
     * Rendu PDF (A4 paysage) du même document.
     */
    public function pdf(Certificat $certificat): string
    {
        return Pdf::loadView('certificats.modele', $this->donnees($certificat))
            ->setPaper('a4', 'landscape')
            // Sans sous-ensemble de police, dompdf embarque l'intégralité de
            // DejaVu Sans : ~900 ko par certificat. Avec, on tombe à ~40 ko —
            // ce qui compte quand on en archive des milliers et qu'on les sert
            // sur les liaisons lentes de l'atelier.
            ->setOption('enable_font_subsetting', true)
            ->output();
    }

    /**
     * Nom de fichier proposé au téléchargement.
     */
    public function nomFichier(Certificat $certificat, string $extension): string
    {
        return "{$certificat->numero_unique}.{$extension}";
    }

    /**
     * Nombre de modules de la formation (affiché comme volume du programme).
     */
    protected function nombreModules(Certificat $certificat): int
    {
        if (! $certificat->formation) {
            return 0;
        }

        return $certificat->formation->modules()->count();
    }

    /**
     * Date en français long — « 26 juillet 2026 » plutôt que « 26/07/2026 » :
     * un document officiel ne s'écrit pas en notation numérique.
     */
    private function dateLongue(mixed $date): ?string
    {
        return $date?->locale('fr')->isoFormat('D MMMM YYYY');
    }

    /**
     * Logo encodé en data-URI. Indispensable : dompdf ne sait pas résoudre un
     * chemin relatif au navigateur comme « /images/… ». Mis en cache car le
     * fichier ne change pas entre deux rendus.
     */
    private function logoDataUri(): ?string
    {
        $chemin = public_path('images/maroc-modis-logo.png');

        if (! is_file($chemin)) {
            return null;
        }

        // La date de modification entre dans la clé : remplacer le logo suffit
        // à invalider le cache, sans vidage manuel.
        return Cache::rememberForever(
            'certificat.logo.'.filemtime($chemin),
            fn () => 'data:image/png;base64,'.base64_encode((string) file_get_contents($chemin)),
        );
    }

    /**
     * QR de vérification, rendu côté serveur en SVG puis encodé en data-URI :
     * dompdf n'exécute pas de JavaScript, le code doit donc être déjà dessiné.
     */
    private function qrDataUri(string $url): ?string
    {
        if (! class_exists(Writer::class)) {
            return null; // l'URL de vérification reste affichée en clair
        }

        $renderer = new ImageRenderer(
            new RendererStyle(
                size: 220,
                margin: 0,
                // Ivoire du papier + encre du document : le QR appartient au
                // document, il n'y est pas collé.
                fill: Fill::uniformColor(new Rgb(253, 252, 248), new Rgb(27, 36, 48)),
            ),
            new SvgImageBackEnd,
        );

        $svg = (new Writer($renderer))->writeString($url);

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}
