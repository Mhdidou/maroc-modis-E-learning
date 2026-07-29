<?php

namespace App\Jobs;

use App\Models\Certificat;
use App\Models\Inscription;
use App\Services\CertificatDocument;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Délivre le certificat d'une formation certifiante terminée : numéro unique,
 * date d'expiration et document archivé sur disque. Exécuté en file (driver
 * database) pour une réponse HTTP instantanée.
 *
 * Idempotent tant que le certificat en cours est valable : redispatcher le job
 * ne produit pas de doublon. En revanche, si le dernier certificat est expiré,
 * une NOUVELLE ligne est créée — la recertification doit laisser une trace, la
 * conservation de l'historique complet étant exigée en audit.
 */
class GenererCertificat implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $inscriptionId) {}

    public function handle(CertificatDocument $document): void
    {
        $inscription = Inscription::with(['utilisateur', 'formation'])->find($this->inscriptionId);

        if (! $inscription || ! $inscription->formation->isCertifiante()) {
            return;
        }

        // Certificat courant = le plus récemment délivré pour ce couple.
        $courant = Certificat::where('utilisateur_id', $inscription->utilisateur_id)
            ->where('formation_id', $inscription->formation_id)
            ->orderByDesc('delivre_le')
            ->first();

        // Déjà délivré et encore valable : rien à faire.
        if ($courant && $courant->estValide() && $courant->chemin_fichier) {
            return;
        }

        // Reprise d'un certificat valable dont le document manque (échec de
        // rendu antérieur) : on régénère le fichier sans renuméroter.
        $certificat = $courant && $courant->estValide()
            ? $courant
            : $this->nouveauCertificat($inscription);

        $certificat->chemin_fichier = $this->archiver($certificat, $document);
        $certificat->save();
    }

    /**
     * Nouvelle délivrance : numéro, date d'émission et échéance de validité.
     */
    private function nouveauCertificat(Inscription $inscription): Certificat
    {
        $delivreLe = Carbon::now();
        $validite = $inscription->formation->validite_mois ?: 24;

        $certificat = new Certificat([
            'utilisateur_id' => $inscription->utilisateur_id,
            'formation_id' => $inscription->formation_id,
            'numero_unique' => $this->numeroUnique($delivreLe),
            'delivre_le' => $delivreLe,
            'expire_le' => (clone $delivreLe)->addMonths($validite),
        ]);

        $certificat->save();

        return $certificat;
    }

    /**
     * Numéro unique et vérifiable : CERT-AAAA-XXXXXXXX (collision improbable,
     * garantie par l'index unique en base — on retente si besoin).
     */
    private function numeroUnique(Carbon $date): string
    {
        do {
            $numero = 'CERT-'.$date->format('Y').'-'.strtoupper(Str::random(8));
        } while (Certificat::where('numero_unique', $numero)->exists());

        return $numero;
    }

    /**
     * Archive le document et retourne son chemin de stockage. Le rendu vient du
     * même gabarit que l'écran (App\Services\CertificatDocument) : PDF si dompdf
     * est disponible, sinon HTML autonome — le fichier reste lisible seul.
     */
    private function archiver(Certificat $certificat, CertificatDocument $document): string
    {
        if (class_exists(Pdf::class)) {
            $chemin = 'certificats/'.$document->nomFichier($certificat, 'pdf');
            Storage::disk('local')->put($chemin, $document->pdf($certificat));

            return $chemin;
        }

        $chemin = 'certificats/'.$document->nomFichier($certificat, 'html');
        Storage::disk('local')->put($chemin, $document->html($certificat));

        return $chemin;
    }
}
