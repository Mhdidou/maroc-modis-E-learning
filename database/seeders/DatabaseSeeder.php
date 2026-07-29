<?php

namespace Database\Seeders;

use App\Models\Certificat;
use App\Models\Chapitre;
use App\Models\CheckpointVideo;
use App\Models\Formation;
use App\Models\Inscription;
use App\Models\JournalConnexion;
use App\Models\Module;
use App\Models\Progression;
use App\Models\Question;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Crée les comptes de démonstration puis un jeu de données réaliste
     * (formations, modules, inscriptions, progressions, certificats, connexions)
     * afin que toutes les fonctionnalités du tableau de bord soient visibles.
     * Mot de passe commun : « password » (à changer en production).
     */
    public function run(): void
    {
        $motDePasse = 'password';

        User::updateOrCreate(
            ['email' => 'admin@triumph.com'],
            [
                'nom_complet' => 'Administrateur du site',
                'mot_de_passe' => $motDePasse,
                'role' => User::ROLE_ADMIN,
            ]
        );

        $superviseur = User::updateOrCreate(
            ['email' => 'superviseur@triumph.com'],
            [
                'nom_complet' => 'Nadia Superviseur',
                'mot_de_passe' => $motDePasse,
                'role' => User::ROLE_SUPERVISEUR,
                'domaine' => 'Couture',
            ]
        );

        $formateur = User::updateOrCreate(
            ['email' => 'formateur@triumph.com'],
            [
                'nom_complet' => 'Karim Formateur',
                'mot_de_passe' => $motDePasse,
                'role' => User::ROLE_FORMATEUR,
                'domaine' => 'Couture',
            ]
        );

        $apprenant1 = User::updateOrCreate(
            ['email' => 'apprenant@triumph.com'],
            [
                'nom_complet' => 'Fatima Apprenante',
                'mot_de_passe' => $motDePasse,
                'role' => User::ROLE_APPRENANT,
                'domaine' => 'Couture',
                'superviseur_id' => $superviseur->id,
            ]
        );

        $apprenant2 = User::updateOrCreate(
            ['email' => 'apprenant2@triumph.com'],
            [
                'nom_complet' => 'Youssef Apprenant',
                'mot_de_passe' => $motDePasse,
                'role' => User::ROLE_APPRENANT,
                'domaine' => 'Coupe',
                'superviseur_id' => $superviseur->id,
            ]
        );

        /* ---------------------------------------------------------------- */
        /*  Formations → chapitres → modules (créées par le formateur) */
        /* ---------------------------------------------------------------- */
        // Chaque chapitre porte une liste de modules [type, titre]. Les modules
        // vidéo reçoivent un checkpoint-surprise, les modules quiz une banque de
        // questions + une configuration (tirage, seuil, durée). Tout est publié.
        $catalogue = [
            'Couture — Points de base' => [
                'type' => 'certifiante',
                'chapitres' => [
                    'Les points fondamentaux' => [
                        ['video', 'Introduction aux points de couture'],
                        ['pdf', 'Fiche technique : le point droit'],
                    ],
                    'Mise en pratique' => [
                        ['video', 'Le point zigzag en pratique'],
                        ['quiz', 'Quiz — Points de base'],
                    ],
                ],
            ],
            'Sécurité en atelier' => [
                'type' => 'non_certifiante',
                'chapitres' => [
                    'Consignes générales' => [
                        ['video', 'Consignes de sécurité machines'],
                        ['pdf', 'Équipements de protection individuelle'],
                        ['quiz', 'Quiz — Sécurité'],
                    ],
                ],
            ],
            'Contrôle qualité lingerie' => [
                'type' => 'certifiante',
                'chapitres' => [
                    'Critères et défauts' => [
                        ['video', 'Les critères qualité Triumph'],
                        ['pdf', 'Grille de contrôle qualité'],
                        ['video', 'Détecter les défauts courants'],
                        ['quiz', 'Quiz — Contrôle qualité'],
                    ],
                ],
            ],
        ];

        /** @var array<string, Formation> $formations */
        $formations = [];

        foreach ($catalogue as $titre => $def) {
            $formation = Formation::firstOrCreate(
                ['titre' => $titre, 'cree_par' => $formateur->id],
                [
                    'type' => $def['type'],
                    'statut' => Formation::STATUT_PUBLIE,
                    'validite_mois' => $def['type'] === Formation::TYPE_CERTIFIANTE ? 24 : null,
                    'description' => 'Formation métier interne Maroc-Modis.',
                ]
            );

            // (Re)construit chapitres + modules seulement s'il n'y en a pas encore.
            if ($formation->chapitres()->count() === 0) {
                $chapPos = 0;
                foreach ($def['chapitres'] as $chapTitre => $modules) {
                    $chapitre = Chapitre::create([
                        'formation_id' => $formation->id,
                        'titre' => $chapTitre,
                        'position' => $chapPos++,
                    ]);

                    foreach ($modules as $position => [$type, $moduleTitre]) {
                        // Contenus de démonstration réels (le formateur importe
                        // ses propres fichiers via l'atelier en production).
                        $contenu = match ($type) {
                            'video' => '/storage/modules/video/demo.mp4',
                            'pdf' => '/storage/modules/pdf/demo.pdf',
                            default => null,
                        };

                        $module = Module::create([
                            'chapitre_id' => $chapitre->id,
                            'type' => $type,
                            'titre' => $moduleTitre,
                            'contenu' => $contenu,
                            'position' => $position,
                            'nb_questions_tirees' => $type === 'quiz' ? 3 : null,
                            'seuil_reussite' => $type === 'quiz' ? 70 : null,
                            'duree_minutes' => $type === 'quiz' ? 10 : null,
                        ]);

                        if ($type === 'video') {
                            $this->semerCheckpoint($module);
                        }

                        if ($type === 'quiz') {
                            $this->semerBanqueQuestions($module);
                        }
                    }
                }
            }

            $formations[$titre] = $formation;
        }

        /* ---------------------------------------------------------------- */
        /*  Inscriptions + progressions */
        /* ---------------------------------------------------------------- */
        // Fatima : Couture en cours (2/4, dont 1 aujourd'hui) + Contrôle qualité terminée.
        $this->inscrire($apprenant1, $formations['Couture — Points de base'], [
            'statut' => 'en_cours',
            'objectif' => 3,
            'modules_faits' => 2,
            'dont_aujourdhui' => 1,
        ]);

        $inscCQ = $this->inscrire($apprenant1, $formations['Contrôle qualité lingerie'], [
            'statut' => 'terminee',
            'objectif' => 2,
            'modules_faits' => 4,
            'dont_aujourdhui' => 0,
            'termine_le' => Carbon::now()->subDays(3),
        ]);

        // Youssef : Couture en cours (1/4 aujourd'hui) + Sécurité non commencée.
        $this->inscrire($apprenant2, $formations['Couture — Points de base'], [
            'statut' => 'en_cours',
            'objectif' => 2,
            'modules_faits' => 1,
            'dont_aujourdhui' => 1,
        ]);

        $this->inscrire($apprenant2, $formations['Sécurité en atelier'], [
            'statut' => 'non_commencee',
            'objectif' => 3,
            'modules_faits' => 0,
            'dont_aujourdhui' => 0,
        ]);

        /* ---------------------------------------------------------------- */
        /*  Certificat (formation certifiante terminée par Fatima) */
        /* ---------------------------------------------------------------- */
        $delivreLe = $inscCQ->termine_le
            ? Carbon::parse($inscCQ->termine_le)
            : Carbon::now()->subDays(3);
        Certificat::firstOrCreate(
            [
                'utilisateur_id' => $apprenant1->id,
                'formation_id' => $formations['Contrôle qualité lingerie']->id,
            ],
            [
                'numero_unique' => 'CERT-'.$delivreLe->format('Y').'-'.strtoupper(Str::random(8)),
                'chemin_fichier' => "certificats/{$apprenant1->id}-controle-qualite.pdf",
                'delivre_le' => $delivreLe,
                'expire_le' => (clone $delivreLe)->addMonths(24),
            ]
        );

        /* ---------------------------------------------------------------- */
        /*  Journal des connexions de la semaine courante */
        /* ---------------------------------------------------------------- */
        $aujourdhui = Carbon::today();
        $lundi = Carbon::now()->startOfWeek(Carbon::MONDAY);

        // Fatima : connectée chaque jour du lundi à aujourd'hui (belle assiduité).
        for ($jour = $lundi->copy(); $jour->lte($aujourdhui); $jour->addDay()) {
            JournalConnexion::firstOrCreate([
                'utilisateur_id' => $apprenant1->id,
                'jour' => $jour->toDateString(),
            ]);
        }

        // Youssef : connecté aujourd'hui uniquement.
        JournalConnexion::firstOrCreate([
            'utilisateur_id' => $apprenant2->id,
            'jour' => $aujourdhui->toDateString(),
        ]);
    }

    /**
     * Inscrit un apprenant à une formation et sème ses progressions.
     *
     * @param  array{statut: string, objectif: int, modules_faits: int, dont_aujourdhui: int, termine_le?: Carbon}  $opts
     */
    private function inscrire(User $apprenant, Formation $formation, array $opts): Inscription
    {
        $inscription = Inscription::updateOrCreate(
            ['utilisateur_id' => $apprenant->id, 'formation_id' => $formation->id],
            [
                'statut' => $opts['statut'],
                'objectif_quotidien' => $opts['objectif'],
                'inscrit_le' => Carbon::now()->subDays(7),
                'termine_le' => $opts['termine_le'] ?? null,
            ]
        );

        // `modules()` traverse les chapitres : on désambiguïse la colonne position.
        $modules = $formation->modules()->orderBy('modules.position')->get();
        $aFaire = $opts['modules_faits'];
        $aujourdhui = $opts['dont_aujourdhui'];

        foreach ($modules as $index => $module) {
            $termine = $index < $aFaire;

            // Les derniers modules terminés sont datés d'aujourd'hui (alimente
            // l'« objectif du jour »), les autres d'un jour antérieur.
            $estAujourdhui = $termine && $index >= ($aFaire - $aujourdhui);
            $termineLe = $termine
                ? ($estAujourdhui ? Carbon::now() : Carbon::now()->subDays(2))
                : null;

            Progression::updateOrCreate(
                ['inscription_id' => $inscription->id, 'module_id' => $module->id],
                [
                    'statut' => $termine ? 'terminee' : 'non_commencee',
                    'score' => $module->type === 'quiz' && $termine ? 85 : null,
                    'termine_le' => $termineLe,
                ]
            );
        }

        return $inscription;
    }

    /**
     * Place un checkpoint-surprise au milieu d'une vidéo (démonstration).
     */
    private function semerCheckpoint(Module $module): void
    {
        CheckpointVideo::create([
            'module_id' => $module->id,
            'position_secondes' => 5,
            'enonce' => 'Quel élément venez-vous de voir à l’écran ?',
            'bonne_reponse' => 'La bonne pratique décrite dans la vidéo',
            'mauvaises_reponses' => [
                'Une pratique interdite',
                'Aucun rapport avec la formation',
            ],
            'explication' => 'La vidéo illustre précisément la bonne pratique attendue en atelier.',
        ]);
    }

    /**
     * Sème une petite banque de questions pour un module quiz (démonstration).
     */
    private function semerBanqueQuestions(Module $module): void
    {
        $banque = [
            ['Quelle est la première étape ?', 'Préparer le poste', ['Commencer sans vérifier', 'Ignorer les consignes']],
            ['Que faire en cas de doute ?', 'Demander au superviseur', ['Continuer quand même', 'Arrêter la production']],
            ['Quel équipement est obligatoire ?', 'Les protections indiquées', ['Aucun', 'Au choix']],
            ['À quelle fréquence contrôler ?', 'Selon la procédure', ['Jamais', 'Une fois par mois']],
            ['Que signifie un défaut détecté ?', 'Écarter la pièce', ['La laisser passer', 'La cacher']],
        ];

        foreach ($banque as [$enonce, $bonne, $mauvaises]) {
            Question::create([
                'module_id' => $module->id,
                'enonce' => $enonce,
                'bonne_reponse' => $bonne,
                'mauvaises_reponses' => $mauvaises,
            ]);
        }
    }
}
