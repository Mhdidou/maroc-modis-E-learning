<?php

namespace App\Http\Controllers;

use App\Models\Certificat;
use App\Services\CertificatDocument;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Trois façons de servir le même document (voir App\Services\CertificatDocument) :
 *
 *   show()       — page Inertia : barre d'actions + document injecté en iframe ;
 *   telecharger()— PDF (dompdf), réservé au titulaire et à l'administrateur ;
 *   verifier()   — page publique d'authentification par numéro, sans compte.
 *
 * L'écran, l'impression et le PDF proviennent du même gabarit : ce que
 * l'apprenant voit est exactement ce qu'il télécharge.
 */
class CertificatController extends Controller
{
    public function __construct(private readonly CertificatDocument $document) {}

    public function show(Request $request, Certificat $certificat): Response
    {
        $this->autoriser($request, $certificat);

        $certificat->load(['formation:id,titre,type']);

        return Inertia::render('Certificats/Apercu', [
            'certificat' => [
                'id' => $certificat->id,
                'numero_unique' => $certificat->numero_unique,
                'titre_formation' => $certificat->formation->titre ?? 'Formation',
                'statut' => $certificat->statut(),
                'expire_le' => optional($certificat->expire_le)->format('d/m/Y'),
            ],
            // Le document lui-même, prêt à être injecté dans une iframe.
            'documentHtml' => $this->document->html($certificat),
        ]);
    }

    /**
     * Téléchargement PDF. Même contrôle d'accès que la consultation : un
     * certificat n'est pas un document public tant qu'on ne passe pas par la
     * vérification par numéro.
     */
    public function telecharger(Request $request, Certificat $certificat): HttpResponse
    {
        $this->autoriser($request, $certificat);

        $pdf = $this->document->pdf($certificat);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'
                .$this->document->nomFichier($certificat, 'pdf').'"',
        ]);
    }

    /**
     * Vérification publique par numéro : permet à un employeur ou à un auditeur
     * de confirmer l'authenticité d'un certificat sans compte.
     *
     * N'expose que le strict nécessaire — nom, formation, dates, validité.
     * Jamais l'e-mail, le rôle ni l'identifiant interne. La route est limitée
     * en débit (throttle) car les numéros sont théoriquement énumérables.
     */
    public function verifier(string $numero): Response
    {
        $certificat = Certificat::with([
            'utilisateur:id,nom_complet,domaine',
            'formation:id,titre,type',
        ])->where('numero_unique', $numero)->first();

        if (! $certificat) {
            return Inertia::render('Certificats/Verification', [
                'numero' => $numero,
                'certificat' => null,
            ]);
        }

        return Inertia::render('Certificats/Verification', [
            'numero' => $numero,
            'certificat' => [
                'apprenant' => $certificat->utilisateur->nom_complet ?? '',
                'domaine' => $certificat->utilisateur->domaine,
                'formation' => $certificat->formation->titre ?? 'Formation',
                'certifiante' => (bool) $certificat->formation?->isCertifiante(),
                'delivre_le' => optional($certificat->delivre_le)->format('d/m/Y'),
                'expire_le' => optional($certificat->expire_le)->format('d/m/Y'),
                'statut' => $certificat->statut(),
            ],
        ]);
    }

    /**
     * Seul le titulaire du certificat ou l'administrateur du site y accède.
     */
    private function autoriser(Request $request, Certificat $certificat): void
    {
        $user = $request->user();

        abort_unless(
            $certificat->utilisateur_id === $user->id || $user->isAdmin(),
            403,
            'Ce certificat ne vous appartient pas.'
        );
    }
}
