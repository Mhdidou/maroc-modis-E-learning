import { Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    BadgeCheck,
    FileText,
    GraduationCap,
    LayoutDashboard,
    LifeBuoy,
    ListChecks,
    LogIn,
    PlayCircle,
    ShieldCheck,
    UserCog,
    Users,
    Video,
} from 'lucide-react';

/**
 * FactoryIndex — Page d'accueil du LMS interne Maroc-Modis (Groupe Triumph).
 *
 * Objectifs :
 *  1. Présenter l'entreprise et valoriser la formation interne.
 *  2. Servir de passerelle de routage vers les 3 espaces (Apprenant / Formateur / Superviseur).
 *
 * Contraintes UI :
 *  - Mobile-first strict (les ouvriers utilisent principalement le smartphone).
 *  - Boutons larges & tactiles, interface épurée.
 *  - Charte : Rouge #E23744, Bleu #1C9AD6, Encre #1B2430.
 */

/* -------------------------------------------------------------------------- */
/*  Charte graphique centralisée (facilite un futur passage en config Tailwind) */
/* -------------------------------------------------------------------------- */
const BRAND = {
    red: '#E23744',
    blue: '#1C9AD6',
    ink: '#1B2430',
};

// Chemin du logo officiel (fichier à déposer dans public/images/).
// Il est servi à la racine du site : /images/maroc-modis-logo.png
const LOGO_SRC = '/images/maroc-modis-logo.png';

/* -------------------------------------------------------------------------- */
/*  Logo officiel Maroc-Modis + intitulé du projet « E-learning »              */
/* -------------------------------------------------------------------------- */
function BrandLogo({ className = '' }: { className?: string }) {
    return (
        <div className={`flex items-center gap-3 ${className}`}>
            {/* Logo officiel (fichier dans public/images/) */}
            <img
                src={LOGO_SRC}
                alt="Logo Maroc-Modis"
                className="h-10 w-auto shrink-0 sm:h-11"
            />
            {/* Nom du projet affiché à côté du logo */}
            <span className="text-lg font-extrabold tracking-tight text-[#1B2430] sm:text-xl">
                E-learning
            </span>
        </div>
    );
}

/* -------------------------------------------------------------------------- */
/*  Carte "Mécanique de la plateforme" (feature)                               */
/* -------------------------------------------------------------------------- */
type FeatureProps = {
    icon: React.ElementType;
    title: string;
    children: React.ReactNode;
    accent: string;
};

function FeatureCard({ icon: Icon, title, children, accent }: FeatureProps) {
    return (
        <div className="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
            <div
                className="mb-4 flex h-12 w-12 items-center justify-center rounded-xl"
                style={{ backgroundColor: `${accent}1A`, color: accent }}
            >
                <Icon className="h-6 w-6" strokeWidth={2.2} />
            </div>
            <h3 className="mb-2 text-lg font-bold text-[#1B2430]">{title}</h3>
            <p className="text-sm leading-relaxed text-slate-600">{children}</p>
        </div>
    );
}

/* -------------------------------------------------------------------------- */
/*  Carte "Portail d'accès" (routage par rôle)                                 */
/* -------------------------------------------------------------------------- */
type PortalProps = {
    icon: React.ElementType;
    title: string;
    description: string;
    href: string;
    accent: string;
};

function PortalCard({ icon: Icon, title, description, href, accent }: PortalProps) {
    return (
        <Link
            href={href}
            className="group flex flex-col rounded-2xl border-2 border-slate-100 bg-white p-6 transition hover:-translate-y-1 hover:shadow-xl focus:outline-none focus-visible:ring-4"
            style={{ ['--tw-ring-color' as string]: `${accent}55` }}
        >
            <div
                className="mb-5 flex h-14 w-14 items-center justify-center rounded-2xl text-white"
                style={{ backgroundColor: accent }}
            >
                <Icon className="h-7 w-7" strokeWidth={2.2} />
            </div>
            <h3 className="text-xl font-extrabold text-[#1B2430]">{title}</h3>
            <p className="mt-2 flex-1 text-sm leading-relaxed text-slate-600">
                {description}
            </p>
            <span
                className="mt-5 inline-flex items-center gap-2 text-sm font-bold"
                style={{ color: accent }}
            >
                Entrer
                <ArrowRight className="h-4 w-4 transition-transform group-hover:translate-x-1" />
            </span>
        </Link>
    );
}

/* -------------------------------------------------------------------------- */
/*  Page                                                                       */
/* -------------------------------------------------------------------------- */
export default function FactoryIndex() {
    const currentYear = new Date().getFullYear();

    // Utilisateur connecté (partagé globalement par Inertia) : le bouton
    // « Connexion Rapide » devient un accès direct à son espace.
    const user = usePage().props.auth?.user;
    const prenom = user?.name?.trim().split(/\s+/)[0];

    return (
        <>
            <Head title="Académie Maroc-Modis · Portail de formation" />

            <div className="min-h-screen bg-slate-50 font-sans text-[#1B2430] antialiased">
                {/* ================= HEADER (sticky) ================= */}
                <header className="sticky top-0 z-50 border-b border-slate-200 bg-white/90 backdrop-blur">
                    <div className="mx-auto flex max-w-6xl items-center justify-between px-4 py-3 sm:px-6">
                        <BrandLogo />

                        {/* Connexion rapide — ou accès direct à l'espace si connecté */}
                        {user ? (
                            <Link
                                href="/dashboard"
                                className="inline-flex items-center gap-2 rounded-xl bg-[#1B2430] px-4 py-2.5 text-sm font-bold text-white transition active:scale-95 hover:bg-black sm:px-5"
                            >
                                <LayoutDashboard className="h-4 w-4" />
                                <span className="hidden xs:inline sm:inline">
                                    Mon espace · {prenom}
                                </span>
                                <span className="xs:hidden sm:hidden">
                                    {prenom}
                                </span>
                            </Link>
                        ) : (
                            <Link
                                href="/login"
                                className="inline-flex items-center gap-2 rounded-xl bg-[#1B2430] px-4 py-2.5 text-sm font-bold text-white transition active:scale-95 hover:bg-black sm:px-5"
                            >
                                <LogIn className="h-4 w-4" />
                                <span className="hidden xs:inline sm:inline">
                                    Connexion Rapide
                                </span>
                                <span className="xs:hidden sm:hidden">
                                    Entrer
                                </span>
                            </Link>
                        )}
                    </div>
                </header>

                <main>
                    {/* ================= HERO ================= */}
                    <section className="relative overflow-hidden bg-[#1B2430] text-white">
                        {/* Placeholder d'ambiance industrielle (dégradés doux) */}
                        <div
                            aria-hidden
                            className="pointer-events-none absolute inset-0 opacity-70"
                            style={{
                                background: `radial-gradient(60% 60% at 85% 10%, ${BRAND.blue}33 0%, transparent 60%), radial-gradient(50% 50% at 10% 90%, ${BRAND.red}33 0%, transparent 60%)`,
                            }}
                        />
                        <div className="relative mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-24">
                           

                            <h1 className="mt-6 max-w-3xl text-4xl font-extrabold leading-tight tracking-tight sm:text-5xl">
                                Votre savoir-faire textile,{' '}
                                <span className="text-[#E23744]">reconnu</span> et{' '}
                                <span className="text-[#1C9AD6]">renforcé</span>.
                            </h1>

                            <p className="mt-5 max-w-2xl text-base leading-relaxed text-slate-300 sm:text-lg">
                                Maroc-Modis met à votre portée des
                                parcours <strong className="text-white">certifiants</strong>{' '}
                                et <strong className="text-white">non-certifiants</strong> :
                                montez en compétence sur les métiers de la lingerie,
                                à votre rythme.
                            </p>

                            {/* CTA principal — large & tactile */}
                            <div className="mt-8 flex flex-col gap-3 sm:flex-row">
                                <Link
                                    href="/login"
                                    className="inline-flex items-center justify-center gap-2 rounded-2xl bg-[#E23744] px-6 py-4 text-base font-bold text-white shadow-lg shadow-[#E23744]/25 transition active:scale-95 hover:brightness-110"
                                >
                                    Accéder au Portail
                                    <ArrowRight className="h-5 w-5" />
                                </Link>
                                <a
                                    href="#portails"
                                    className="inline-flex items-center justify-center gap-2 rounded-2xl border border-white/20 px-6 py-4 text-base font-semibold text-white transition active:scale-95 hover:bg-white/10"
                                >
                                    Découvrir les espaces
                                </a>
                            </div>
                        </div>
                    </section>

                    {/* ================= MÉCANIQUE DE LA PLATEFORME ================= */}
                    <section className="mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-20">
                        <div className="mx-auto max-w-2xl text-center">
                            <h2 className="text-3xl font-extrabold sm:text-4xl">
                                Un apprentissage qui garantit la compréhension
                            </h2>
                            <p className="mt-4 text-slate-600">
                                Nos formations ne se contentent pas de diffuser du
                                contenu : elles s'assurent que chaque notion est
                                réellement acquise avant de passer à la suite.
                            </p>
                        </div>

                        <div className="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                            <FeatureCard
                                icon={PlayCircle}
                                title="Apprentissage interactif"
                                accent={BRAND.blue}
                            >
                                Les vidéos intègrent des points de contrôle : des
                                questions obligatoires valident votre compréhension
                                avant de pouvoir continuer. Impossible de passer à
                                côté de l'essentiel.
                            </FeatureCard>

                            <FeatureCard
                                icon={Video}
                                title="Supports variés"
                                accent={BRAND.red}
                            >
                                Vidéos de démonstration, fiches PDF téléchargeables et
                                quiz : chaque module combine les
                                formats les plus adaptés au geste métier.
                            </FeatureCard>

                            <FeatureCard
                                icon={ListChecks}
                                title="Progression suivie"
                                accent={BRAND.blue}
                            >
                                Votre avancement est enregistré étape par étape. Vous
                                reprenez toujours exactement là où vous vous êtes
                                arrêté, sans jamais perdre votre progression.
                            </FeatureCard>

                            <FeatureCard
                                icon={BadgeCheck}
                                title="Certificats internes"
                                accent={BRAND.red}
                            >
                                À la clé : des certificats internes reconnus par le
                                Groupe Triumph, qui valorisent officiellement vos
                                nouvelles compétences au sein de l'usine.
                            </FeatureCard>
                        </div>
                    </section>

                    {/* ================= PORTAILS D'ACCÈS ================= */}
                    <section
                        id="portails"
                        className="border-t border-slate-200 bg-white py-16 sm:py-20"
                    >
                        <div className="mx-auto max-w-6xl px-4 sm:px-6">
                            <div className="mx-auto max-w-2xl text-center">
                                <h2 className="text-3xl font-extrabold sm:text-4xl">
                                    Choisissez votre espace
                                </h2>
                                <p className="mt-4 text-slate-600">
                                    Chaque rôle dispose d'une interface dédiée.
                                    Sélectionnez le vôtre pour accéder aux bons outils.
                                </p>
                            </div>

                            <div className="mt-12 grid gap-6 md:grid-cols-3">
                                <PortalCard
                                    icon={GraduationCap}
                                    title="Espace Apprenant"
                                    accent={BRAND.red}
                                    href="/login"
                                    description="Interface simplifiée pensée pour les ouvriers : accédez à vos formations, suivez les vidéos et décrochez vos certificats en quelques appuis."
                                />
                                <PortalCard
                                    icon={UserCog}
                                    title="Espace Formateur"
                                    accent={BRAND.blue}
                                    href="/login"
                                    description="Créez et organisez les contenus, ajoutez les quiz et les points de contrôle, puis publiez vos modules de formation."
                                />
                                <PortalCard
                                    icon={Users}
                                    title="Espace Superviseur"
                                    accent={BRAND.ink}
                                    href="/login"
                                    description="Pilotez la montée en compétence des équipes : suivi des progressions, taux de réussite et statistiques par atelier."
                                />
                            </div>
                        </div>
                    </section>
                </main>

                {/* ================= FOOTER UTILITAIRE ================= */}
                <footer className="bg-[#1B2430] text-slate-300">
                    <div className="mx-auto grid max-w-6xl gap-10 px-4 py-12 sm:px-6 md:grid-cols-3">
                        {/* Identité */}
                        <div>
                            <div className="flex items-center gap-3">
                                {/* Pastille blanche pour faire ressortir le logo sur fond sombre */}
                                <span className="flex h-11 items-center rounded-lg bg-white px-2">
                                    <img
                                        src={LOGO_SRC}
                                        alt="Logo Maroc-Modis"
                                        className="h-8 w-auto"
                                    />
                                </span>
                                <span className="text-lg font-extrabold text-white">
                                    E-learning
                                </span>
                            </div>
                            <p className="mt-4 max-w-xs text-sm text-slate-400">
                                Plateforme de formation interne dédiée aux équipes de
                                production textile. Réservée à un usage professionnel.
                            </p>
                        </div>

                        {/* Support technique / Helpdesk */}
                        <div>
                            <h4 className="flex items-center gap-2 text-sm font-bold uppercase tracking-wide text-white">
                                <LifeBuoy className="h-4 w-4 text-[#1C9AD6]" />
                                Support technique
                            </h4>
                            <p className="mt-3 text-sm text-slate-400">
                                Mot de passe oublié, connexion ou bug ? Contactez le
                                Helpdesk interne.
                            </p>
                            <ul className="mt-3 space-y-1 text-sm">
                                <li>
                                    <a href="mailto:helpdesk@triumph.com" className="font-medium text-white hover:text-[#1C9AD6]">
                                        helpdesk@triumph.com
                                    </a>
                                </li>
                                <li className="text-slate-400">
                                    Poste interne : <span className="font-semibold text-white">1234</span>
                                </li>
                            </ul>
                        </div>

                        {/* Ressources Humaines */}
                        <div>
                            <h4 className="flex items-center gap-2 text-sm font-bold uppercase tracking-wide text-white">
                                <FileText className="h-4 w-4 text-[#E23744]" />
                                Ressources Humaines
                            </h4>
                            <p className="mt-3 text-sm text-slate-400">
                                Questions sur l'impact des certificats sur votre
                                parcours et votre évolution de carrière ?
                            </p>
                            <ul className="mt-3 space-y-1 text-sm">
                                <li>
                                    <a href="mailto:rh@triumph.com" className="font-medium text-white hover:text-[#E23744]">
                                        rh@triumph.com
                                    </a>
                                </li>
                                <li className="text-slate-400">
                                    Bureau RH · Bâtiment administratif
                                </li>
                            </ul>
                        </div>
                    </div>

                    {/* Mentions légales internes */}
                    <div className="border-t border-white/10">
                        <div className="mx-auto flex max-w-6xl flex-col items-center justify-between gap-2 px-4 py-5 text-xs text-slate-500 sm:flex-row sm:px-6">
                            <p>
                                © {currentYear} Groupe Triumph — Maroc-Modis. Tous
                                droits réservés. Usage strictement interne.
                            </p>
                        </div>
                    </div>
                </footer>
            </div>
        </>
    );
}
