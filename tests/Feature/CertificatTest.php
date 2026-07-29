<?php

use App\Jobs\GenererCertificat;
use App\Models\Certificat;
use App\Models\Chapitre;
use App\Models\Formation;
use App\Models\Inscription;
use App\Models\Module;
use App\Models\User;
use App\Services\CertificatDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

/**
 * Délivre un certificat prêt à l'emploi (apprenant, formation certifiante de
 * deux leçons, formateur auteur). `$expireLe` permet de fabriquer un
 * certificat déjà périmé.
 */
function certificatDelivre(
    ?string $expireLe = '+2 years',
    string $type = 'certifiante',
    string $numero = 'CERT-2026-TESTTEST',
): Certificat {
    $formateur = User::factory()->formateur()->create(['nom_complet' => 'Youssef Benali']);
    $apprenant = User::factory()->create([
        'nom_complet' => 'Fatima Zahra El Amrani',
        'domaine' => 'Couture',
    ]);

    $formation = Formation::create([
        'titre' => 'Sécurité machine', 'type' => $type, 'statut' => 'publie',
        'validite_mois' => 24, 'cree_par' => $formateur->id,
    ]);
    $chapitre = Chapitre::create(['formation_id' => $formation->id, 'titre' => 'C', 'position' => 0]);
    foreach (range(0, 1) as $i) {
        Module::create([
            'chapitre_id' => $chapitre->id, 'type' => 'pdf',
            'titre' => "M$i", 'position' => $i, 'contenu' => "m$i.pdf",
        ]);
    }

    return Certificat::create([
        'utilisateur_id' => $apprenant->id,
        'formation_id' => $formation->id,
        'numero_unique' => $numero,
        'delivre_le' => now()->subMonth(),
        'expire_le' => $expireLe ? now()->modify($expireLe) : null,
    ]);
}

/* -------------------------------------------------------------------------- */
/*  Le document : gabarit unique, autonome, porteur des données réelles */
/* -------------------------------------------------------------------------- */

it('produit un document autonome contenant les données de l’apprenant', function () {
    $certificat = certificatDelivre();

    $html = app(CertificatDocument::class)->html($certificat);

    // Les données réelles, celles qui manquaient à l'écran auparavant.
    expect($html)
        ->toContain('Fatima Zahra El Amrani')   // titulaire
        ->toContain('Sécurité machine')          // formation
        ->toContain('Couture')                   // atelier
        ->toContain('Youssef Benali')            // formateur (jamais joint avant)
        ->toContain('CERT-2026-TESTTEST')        // numéro vérifiable
        ->toContain('2 leçons');                 // volume du programme

    // Autonome : aucune ressource externe, tout est en data-URI.
    expect($html)
        ->toContain('data:image/png;base64,')    // logo
        ->toContain('data:image/svg+xml;base64,'); // QR de vérification

    preg_match_all('/(?:src|href)\s*=\s*"(?!data:)([^"]*)"/i', $html, $m);
    expect($m[1])->toBeEmpty();
});

it('mentionne les dates de délivrance et d’expiration en clair', function () {
    $certificat = certificatDelivre();

    $html = app(CertificatDocument::class)->html($certificat);

    expect($html)
        ->toContain($certificat->delivre_le->locale('fr')->isoFormat('D MMMM YYYY'))
        ->toContain($certificat->expire_le->locale('fr')->isoFormat('D MMMM YYYY'));
});

it('porte l’URL de vérification, scannable et lisible', function () {
    $certificat = certificatDelivre();

    $html = app(CertificatDocument::class)->html($certificat);

    // En clair : une photocopie reste vérifiable sans scanner le QR.
    expect($html)->toContain(route('certificats.verifier', $certificat->numero_unique));
});

it('signale l’expiration sur le document lui-même', function () {
    expect(app(CertificatDocument::class)->html(certificatDelivre('-1 day')))
        ->toContain('Certificat expiré');

    expect(app(CertificatDocument::class)->html(
        certificatDelivre('+2 years', 'certifiante', 'CERT-2026-VALIDE01')
    ))->not->toContain('Certificat expiré');
});

it('génère un PDF A4 paysage', function () {
    $pdf = app(CertificatDocument::class)->pdf(certificatDelivre());

    expect($pdf)->toStartWith('%PDF-');
    // Le sous-ensemble de police doit rester actif : sans lui, ~900 ko.
    expect(strlen($pdf))->toBeLessThan(300_000);
});

/* -------------------------------------------------------------------------- */
/*  États de validité */
/* -------------------------------------------------------------------------- */

it('distingue valide, bientôt expiré et expiré', function () {
    expect(certificatDelivre('+2 years', 'certifiante', 'CERT-A')->statut())->toBe('valide');
    expect(certificatDelivre('+20 days', 'certifiante', 'CERT-B')->statut())->toBe('bientot_expire');
    expect(certificatDelivre('-1 day', 'certifiante', 'CERT-C')->statut())->toBe('expire');
    expect(certificatDelivre(null, 'certifiante', 'CERT-D')->statut())->toBe('valide'); // sans échéance
});

it('exclut les certificats expirés des compteurs de certifications acquises', function () {
    certificatDelivre('+2 years', 'certifiante', 'CERT-VALIDE');
    certificatDelivre('-1 day', 'certifiante', 'CERT-PERIME');

    expect(Certificat::valides()->count())->toBe(1);
    expect(Certificat::expires()->count())->toBe(1);
    expect(Certificat::count())->toBe(2); // l'historique reste complet
});

/* -------------------------------------------------------------------------- */
/*  Accès : consultation, téléchargement, vérification publique */
/* -------------------------------------------------------------------------- */

it('affiche le certificat à son titulaire, document inclus', function () {
    $certificat = certificatDelivre();

    $this->actingAs($certificat->utilisateur)
        ->get(route('certificats.show', $certificat))
        ->assertOk();
});

it('refuse la consultation et le téléchargement à un tiers', function () {
    $certificat = certificatDelivre();
    $intrus = User::factory()->create();

    $this->actingAs($intrus)->get(route('certificats.show', $certificat))->assertForbidden();
    $this->actingAs($intrus)->get(route('certificats.telecharger', $certificat))->assertForbidden();
});

it('autorise l’administrateur du site', function () {
    $certificat = certificatDelivre();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get(route('certificats.show', $certificat))->assertOk();
});

it('télécharge le certificat en PDF', function () {
    $certificat = certificatDelivre();

    $reponse = $this->actingAs($certificat->utilisateur)
        ->get(route('certificats.telecharger', $certificat))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    expect($reponse->headers->get('content-disposition'))
        ->toContain('CERT-2026-TESTTEST.pdf');
    expect($reponse->getContent())->toStartWith('%PDF-');
});

it('exige une authentification pour télécharger', function () {
    $certificat = certificatDelivre();

    $this->get(route('certificats.telecharger', $certificat))->assertRedirect(route('login'));
});

it('vérifie publiquement un certificat par son numéro, sans compte', function () {
    $certificat = certificatDelivre();

    $this->get(route('certificats.verifier', $certificat->numero_unique))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Certificats/Verification')
            ->where('certificat.apprenant', 'Fatima Zahra El Amrani')
            ->where('certificat.formation', 'Sécurité machine')
            ->where('certificat.statut', 'valide')
        );
});

it('n’expose jamais de donnée personnelle superflue à la vérification', function () {
    $certificat = certificatDelivre();

    $this->get(route('certificats.verifier', $certificat->numero_unique))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->missing('certificat.email')
            ->missing('certificat.role')
            ->missing('certificat.id')
            ->missing('certificat.utilisateur_id')
        );
});

it('répond sans divulguer quoi que ce soit pour un numéro inconnu', function () {
    $this->get(route('certificats.verifier', 'CERT-2026-INCONNU0'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Certificats/Verification')
            ->where('certificat', null)
        );
});

/* -------------------------------------------------------------------------- */
/*  Délivrance et recertification */
/* -------------------------------------------------------------------------- */

it('archive le document à la délivrance', function () {
    Storage::fake('local');
    $certificat = certificatDelivre();
    $inscription = Inscription::create([
        'utilisateur_id' => $certificat->utilisateur_id,
        'formation_id' => $certificat->formation_id,
        'statut' => 'terminee',
    ]);
    $certificat->delete(); // on repart d'une formation terminée, sans certificat

    (new GenererCertificat($inscription->id))->handle(app(CertificatDocument::class));

    $delivre = Certificat::where('utilisateur_id', $inscription->utilisateur_id)->sole();
    expect($delivre->numero_unique)->toStartWith('CERT-');
    Storage::disk('local')->assertExists($delivre->chemin_fichier);
});

it('ne délivre pas deux fois tant que le certificat est valable', function () {
    Storage::fake('local');
    $certificat = certificatDelivre('+2 years');
    $certificat->update(['chemin_fichier' => 'certificats/deja.pdf']);
    $inscription = Inscription::create([
        'utilisateur_id' => $certificat->utilisateur_id,
        'formation_id' => $certificat->formation_id,
        'statut' => 'terminee',
    ]);

    (new GenererCertificat($inscription->id))->handle(app(CertificatDocument::class));

    expect(Certificat::count())->toBe(1);
});

it('délivre un nouveau certificat après expiration en conservant l’ancien', function () {
    Storage::fake('local');
    $perime = certificatDelivre('-1 day');
    $perime->update(['chemin_fichier' => 'certificats/perime.pdf']);
    $inscription = Inscription::create([
        'utilisateur_id' => $perime->utilisateur_id,
        'formation_id' => $perime->formation_id,
        'statut' => 'terminee',
    ]);

    (new GenererCertificat($inscription->id))->handle(app(CertificatDocument::class));

    // L'historique complet est exigé en audit : deux lignes, pas un écrasement.
    expect(Certificat::count())->toBe(2);

    $courant = Certificat::orderByDesc('delivre_le')->first();
    expect($courant->id)->not->toBe($perime->id);
    expect($courant->estValide())->toBeTrue();
    expect($courant->numero_unique)->not->toBe($perime->numero_unique);
    expect(Certificat::find($perime->id))->not->toBeNull();
});

it('ne délivre aucun certificat pour une formation non certifiante', function () {
    Storage::fake('local');
    $certificat = certificatDelivre('+2 years', 'non_certifiante');
    $inscription = Inscription::create([
        'utilisateur_id' => $certificat->utilisateur_id,
        'formation_id' => $certificat->formation_id,
        'statut' => 'terminee',
    ]);
    $certificat->delete();

    (new GenererCertificat($inscription->id))->handle(app(CertificatDocument::class));

    expect(Certificat::count())->toBe(0);
});
