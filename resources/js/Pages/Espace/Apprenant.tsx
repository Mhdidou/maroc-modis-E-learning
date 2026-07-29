import ApercuBanner from '@/Components/ApercuBanner';
import ObjectifDuJour, { ObjectifDuJourData } from '@/Components/ObjectifDuJour';
import ProgressBar from '@/Components/ProgressBar';
import StatCard from '@/Components/StatCard';
import WeekActivity, { JourActivite } from '@/Components/WeekActivity';
import { BRAND } from '@/brand';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, usePage } from '@inertiajs/react';
import {
    Award,
    BookOpen,
    CheckCircle2,
    Download,
    Eye,
    PlayCircle,
} from 'lucide-react';

type Formation = {
    id: number;
    inscription_id: number;
    titre: string;
    type: string;
    statut: string;
    objectif_quotidien: number;
    modules_total: number;
    modules_faits: number;
    progression: number;
};

type StatutCertificat = 'valide' | 'bientot_expire' | 'expire';

type Certificat = {
    id: number;
    numero_unique: string;
    titre: string;
    delivre_le: string | null;
    expire_le: string | null;
    statut: StatutCertificat;
};

const STATUT_LABEL: Record<string, { label: string; classe: string }> = {
    non_commencee: { label: 'À commencer', classe: 'bg-slate-100 text-slate-600' },
    en_cours: { label: 'En cours', classe: 'bg-blue-100 text-blue-700' },
    terminee: { label: 'Terminée', classe: 'bg-green-100 text-green-700' },
};

// Validité du certificat : sans cette pastille, un certificat périmé était
// indiscernable d'un certificat valable dans la liste.
const ETAT_CERTIFICAT: Record<
    StatutCertificat,
    { label: string; classe: string } | null
> = {
    valide: null, // l'état normal ne mérite pas de pastille
    bientot_expire: {
        label: 'Bientôt expiré',
        classe: 'bg-amber-100 text-amber-800',
    },
    expire: { label: 'Expiré', classe: 'bg-red-100 text-red-700' },
};

function EtatCertificat({ statut }: { statut: StatutCertificat }) {
    const etat = ETAT_CERTIFICAT[statut];

    if (!etat) return null;

    return (
        <span
            className={`rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide ${etat.classe}`}
        >
            {etat.label}
        </span>
    );
}

export default function Apprenant({
    stats,
    formations,
    objectifDuJour,
    activiteSemaine,
    certificats = [],
    apercu,
}: {
    stats: {
        inscriptions: number;
        en_cours: number;
        terminees: number;
        certificats: number;
    };
    formations: Formation[];
    objectifDuJour: ObjectifDuJourData;
    activiteSemaine: { jours: JourActivite[]; actifs: number };
    certificats?: Certificat[];
    apercu?: string;
}) {
    const user = usePage().props.auth.user;

    return (
        <AuthenticatedLayout
            header={
                <div>
                    <h1 className="text-2xl font-extrabold">
                        Bonjour {user.name} 👋
                    </h1>
                    <p className="mt-1 text-sm text-slate-500">
                        Voici l'avancement de vos formations
                        {user.domaine ? ` — atelier ${user.domaine}` : ''}.
                    </p>
                </div>
            }
        >
            <Head title="Espace Apprenant" />

            {apercu && <ApercuBanner role="apprenant" />}

            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <StatCard
                    icon={BookOpen}
                    label="Formations suivies"
                    value={stats.inscriptions}
                    accent={BRAND.red}
                />
                <StatCard
                    icon={PlayCircle}
                    label="En cours"
                    value={stats.en_cours}
                    accent={BRAND.blue}
                />
                <StatCard
                    icon={CheckCircle2}
                    label="Terminées"
                    value={stats.terminees}
                    accent="#16a34a"
                />
                <StatCard
                    icon={Award}
                    label="Certificats obtenus"
                    value={stats.certificats}
                    accent={BRAND.ink}
                />
            </div>

            {/* Objectif du jour + activité de la semaine */}
            <div className="mt-8 grid gap-6 lg:grid-cols-2">
                <ObjectifDuJour {...objectifDuJour} />
                <WeekActivity
                    jours={activiteSemaine.jours}
                    actifs={activiteSemaine.actifs}
                />
            </div>

            {/* Mes formations avec progression */}
            <section className="mt-8 rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
                <div className="mb-4 flex items-center justify-between">
                    <h2 className="text-lg font-bold">Mes formations</h2>
                    {!apercu && formations.length > 0 && (
                        <Link
                            href={route('mes-formations.index')}
                            className="text-sm font-semibold text-[#1C9AD6] hover:underline"
                        >
                            Tout voir →
                        </Link>
                    )}
                </div>

                {formations.length === 0 ? (
                    <p className="rounded-xl bg-slate-50 p-6 text-center text-sm text-slate-500">
                        Vous n'êtes inscrit à aucune formation pour le moment.
                        Votre superviseur vous inscrira prochainement.
                    </p>
                ) : (
                    <ul className="divide-y divide-slate-100">
                        {formations.map((f) => {
                            const s =
                                STATUT_LABEL[f.statut] ??
                                STATUT_LABEL.non_commencee;
                            return (
                                <li key={f.inscription_id} className="py-4">
                                    <div className="mb-2 flex items-center justify-between gap-3">
                                        <span className="font-semibold text-[#1B2430]">
                                            {f.titre}
                                        </span>
                                        <span
                                            className={`shrink-0 rounded-full px-3 py-1 text-xs font-bold ${s.classe}`}
                                        >
                                            {s.label}
                                        </span>
                                    </div>
                                    <ProgressBar value={f.progression} />
                                </li>
                            );
                        })}
                    </ul>
                )}
            </section>

            {/* Mes certificats */}
            <section className="mt-8 rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
                <h2 className="mb-4 flex items-center gap-2 text-lg font-bold">
                    <Award className="h-5 w-5 text-[#16a34a]" />
                    Mes certificats
                </h2>

                {certificats.length === 0 ? (
                    <p className="rounded-xl bg-slate-50 p-6 text-center text-sm text-slate-500">
                        Aucun certificat pour le moment. Terminez une formation
                        certifiante pour l'obtenir.
                    </p>
                ) : (
                    <ul className="divide-y divide-slate-100">
                        {certificats.map((c) => (
                            <li
                                key={c.id}
                                className="flex flex-wrap items-center justify-between gap-3 py-3"
                            >
                                <div>
                                    <p className="flex flex-wrap items-center gap-2 font-semibold text-[#1B2430]">
                                        {c.titre}
                                        <EtatCertificat statut={c.statut} />
                                    </p>
                                    <p className="text-xs text-slate-500">
                                        Délivré le {c.delivre_le ?? '—'}
                                        {c.expire_le && (
                                            <>
                                                {' · '}
                                                {c.statut === 'expire'
                                                    ? 'expiré le '
                                                    : 'valable jusqu’au '}
                                                {c.expire_le}
                                            </>
                                        )}
                                    </p>
                                </div>
                                <div className="flex items-center gap-2">
                                    <Link
                                        href={route('certificats.show', c.id)}
                                        className="inline-flex items-center gap-1.5 rounded-lg bg-[#1B2430] px-3 py-2 text-xs font-bold text-white transition hover:bg-black"
                                    >
                                        <Eye className="h-3.5 w-3.5" />
                                        Voir
                                    </Link>
                                    <a
                                        href={route(
                                            'certificats.telecharger',
                                            c.id,
                                        )}
                                        className="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-2 text-xs font-bold text-slate-600 transition hover:bg-slate-100"
                                    >
                                        <Download className="h-3.5 w-3.5" />
                                        PDF
                                    </a>
                                </div>
                            </li>
                        ))}
                    </ul>
                )}
            </section>
        </AuthenticatedLayout>
    );
}
