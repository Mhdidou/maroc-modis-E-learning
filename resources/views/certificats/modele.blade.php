{{--
    Document « certificat » — gabarit unique (écran, impression, PDF, fichier).

    Contraintes assumées :
      · Autonome : CSS en ligne, logo et QR en data-URI, zéro requête externe.
        Le fichier reste valide ouvert seul, hors du LMS, dans dix ans.
      · Compatible dompdf : pas de flexbox ni de grid (non supportés), mise en
        page en flux + un tableau pour le pied de page, cotes en millimètres.
      · Polices : dompdf n'embarque que Times, Helvetica, Courier et DejaVu Sans.
        Le serif d'affichage est donc Times côté PDF ; les navigateurs prennent
        le premier serif disponible de la pile. Pour un serif de labeur (EB
        Garamond…), déposer le .ttf et déclarer un @font-face : rien d'autre à
        changer ici.

    Parti pris graphique : le prestige vient de la retenue, pas de l'ornement.
    Un seul accent (bronze) pour l'encadrement, une seule encre déclinée en
    opacités, papier ivoire. Les couleurs de marque ne servent que le logo et
    un filet de 3px en pied — présentes, mais silencieuses.
--}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Certificat {{ $numero }}</title>
    <style>
        @page { size: A4 landscape; margin: 0; }

        html, body { margin: 0; padding: 0; }

        body {
            background: #FDFCF8;
            color: #1B2430;
            font-family: "DejaVu Sans", Helvetica, Arial, sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        /* Feuille A4 paysage exacte : le document a une taille physique, pas
           une taille de fenêtre. À l'écran comme au PDF. */
        .feuille {
            position: relative;
            width: 297mm;
            height: 210mm;
            margin: 0 auto;
            background: #FDFCF8;
            overflow: hidden;
        }

        /* Double filet bronze — l'unique ornement. 1.5pt + 0.5pt, façon
           gravure : deux traits fins valent mieux qu'une bordure épaisse. */
        .filet-externe {
            position: absolute;
            top: 10mm; right: 10mm; bottom: 10mm; left: 10mm;
            border: 1.5pt solid #8C6B3F;
        }
        .filet-interne {
            position: absolute;
            top: 12mm; right: 12mm; bottom: 12mm; left: 12mm;
            border: 0.5pt solid #8C6B3F;
        }

        .contenu {
            position: absolute;
            top: 12mm; right: 12mm; bottom: 12mm; left: 12mm;
            /* Rythme vertical calé pour que le bloc central occupe l'espace
               laissé libre par le pied (positionné en absolu) : sans cela le
               document est haut perché et laisse un vide de ~50 mm. */
            padding: 25mm 20mm 0 20mm;
            text-align: center;
        }

        .logo { height: 12mm; width: auto; }

        .surtitre {
            margin: 7mm 0 0 0;
            font-size: 10pt;
            font-weight: bold;
            letter-spacing: 0.34em;
            text-transform: uppercase;
            color: #8C6B3F;
        }

        /* Séparateur : filet — losange — filet. Rendu en table pour que dompdf
           aligne les trois cellules sans flexbox. */
        .separateur {
            width: 62mm;
            margin: 4mm auto 0 auto;
            border-collapse: collapse;
        }
        .separateur td { padding: 0; vertical-align: middle; }
        .separateur .trait { border-top: 0.5pt solid #C9B896; }
        .separateur .losange {
            width: 8mm;
            font-size: 7pt;
            color: #8C6B3F;
            line-height: 1;
        }

        .mention {
            margin: 13mm 0 0 0;
            font-family: "Times New Roman", Times, serif;
            font-style: italic;
            font-size: 12pt;
            color: rgba(27, 36, 48, 0.65);
        }

        /* Le nom : la seule chose qu'on doit voir à trois mètres. */
        .apprenant {
            margin: 4mm 0 0 0;
            font-family: "Times New Roman", Times, serif;
            font-size: 36pt;
            font-weight: normal;
            line-height: 1.1;
            color: #1B2430;
        }

        .filet-nom {
            width: 120mm;
            margin: 3mm auto 0 auto;
            border-top: 0.5pt solid #C9B896;
        }

        .domaine {
            margin: 2.5mm 0 0 0;
            font-size: 8.5pt;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: rgba(27, 36, 48, 0.5);
        }

        .programme {
            margin: 14mm 0 0 0;
            font-family: "Times New Roman", Times, serif;
            font-style: italic;
            font-size: 11.5pt;
            color: rgba(27, 36, 48, 0.65);
        }

        .formation {
            margin: 2.5mm 0 0 0;
            font-family: "Times New Roman", Times, serif;
            font-size: 18pt;
            font-weight: bold;
            line-height: 1.25;
            color: #1B2430;
        }

        .volume {
            margin: 2mm 0 0 0;
            font-size: 8.5pt;
            letter-spacing: 0.1em;
            color: rgba(27, 36, 48, 0.5);
        }

        /* Bandeau « expiré » : pas de filigrane incliné — dompdf ne gère les
           rotations que partiellement. Un bandeau droit est fiable partout et
           se lit tout aussi bien. */
        .bandeau-expire {
            margin: 6mm auto 0 auto;
            padding: 2mm 6mm;
            display: inline-block;
            border: 0.75pt solid #B23A34;
            font-size: 8.5pt;
            font-weight: bold;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: #B23A34;
        }

        /* Pied : QR + références à gauche, signatures à droite. */
        .pied {
            position: absolute;
            bottom: 13mm;
            left: 20mm;
            right: 20mm;
            width: auto;
            border-collapse: collapse;
        }
        .pied td { vertical-align: bottom; padding: 0; }

        .qr { width: 21mm; height: 21mm; }

        .references {
            padding-left: 4mm !important;
            text-align: left;
            font-size: 7.5pt;
            line-height: 1.6;
            color: rgba(27, 36, 48, 0.6);
        }
        .references .etiquette {
            letter-spacing: 0.12em;
            text-transform: uppercase;
            font-size: 6.5pt;
            color: rgba(27, 36, 48, 0.42);
        }
        /* Le numéro est un identifiant officiel : le monospace le signale. */
        .numero {
            font-family: "Courier New", Courier, monospace;
            font-size: 8pt;
            letter-spacing: 0.04em;
            color: #1B2430;
        }
        .lien {
            font-size: 6.5pt;
            color: rgba(27, 36, 48, 0.42);
        }

        .signature {
            width: 52mm;
            text-align: center;
            padding-left: 6mm !important;
        }
        .signature .ligne {
            border-top: 0.5pt solid #1B2430;
            margin-bottom: 1.5mm;
        }
        .signature .qualite {
            font-size: 6.5pt;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: rgba(27, 36, 48, 0.45);
        }
        .signature .nom {
            font-family: "Times New Roman", Times, serif;
            font-size: 9.5pt;
            color: #1B2430;
        }

        /* Marque, en dernier et en tout petit : filet rouge/bleu au ras du bord. */
        .filet-marque {
            position: absolute;
            bottom: 0; left: 0;
            width: 297mm;
            height: 3px;
            border-collapse: collapse;
        }
        .filet-marque td { padding: 0; height: 3px; font-size: 0; line-height: 0; }
        .filet-marque .rouge { background: #E23744; }
        .filet-marque .bleu { background: #1C9AD6; }
    </style>
</head>
<body>
    <div class="feuille">
        <div class="filet-externe"></div>
        <div class="filet-interne"></div>

        <div class="contenu">
            @if ($logo)
                <img class="logo" src="{{ $logo }}" alt="Maroc-Modis">
            @endif

            <p class="surtitre">
                {{ $certifiante ? 'Certificat de réussite' : 'Attestation de suivi' }}
            </p>

            <table class="separateur">
                <tr>
                    <td class="trait"></td>
                    <td class="losange">&#9670;</td>
                    <td class="trait"></td>
                </tr>
            </table>

            <p class="mention">Ce document atteste que</p>

            <h1 class="apprenant">{{ $apprenant }}</h1>

            <div class="filet-nom"></div>

            @if ($domaine)
                <p class="domaine">Atelier {{ $domaine }}</p>
            @endif

            <p class="programme">
                a suivi et validé l'intégralité du programme
            </p>

            <h2 class="formation">{{ $formation }}</h2>

            <p class="volume">
                {{ $certifiante ? 'Formation certifiante' : 'Formation' }}
                @if ($nb_modules > 0)
                    &middot; {{ $nb_modules }} {{ $nb_modules > 1 ? 'leçons' : 'leçon' }}
                @endif
            </p>

            @if ($est_expire)
                <div class="bandeau-expire">
                    Certificat expiré &mdash; renouvellement requis
                </div>
            @endif
        </div>

        <table class="pied">
            <tr>
                @if ($qr)
                    <td style="width: 21mm;">
                        <img class="qr" src="{{ $qr }}" alt="Vérification">
                    </td>
                @endif

                <td class="references">
                    <span class="etiquette">Délivré le</span> {{ $delivre_le ?? '—' }}<br>
                    @if ($expire_le)
                        <span class="etiquette">Valable jusqu'au</span> {{ $expire_le }}<br>
                    @endif
                    <span class="numero">{{ $numero }}</span><br>
                    {{-- URL en clair : le QR ne sert à rien pour qui ne peut pas
                         le scanner (auditeur sur papier, photocopie). --}}
                    <span class="lien">{{ $url_verification }}</span>
                </td>

                @if ($formateur)
                    <td class="signature">
                        <div class="ligne"></div>
                        <div class="qualite">Le formateur</div>
                        <div class="nom">{{ $formateur }}</div>
                    </td>
                @endif

                <td class="signature">
                    <div class="ligne"></div>
                    <div class="qualite">Direction</div>
                    <div class="nom">Maroc-Modis &middot; Groupe Triumph</div>
                </td>
            </tr>
        </table>

        <table class="filet-marque">
            <tr>
                <td class="rouge"></td>
                <td class="bleu"></td>
            </tr>
        </table>
    </div>
</body>
</html>
