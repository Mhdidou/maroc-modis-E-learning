import { BRAND } from '@/brand';
import StatCard from '@/Components/StatCard';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, usePage } from '@inertiajs/react';
import { Award, BookOpen, CheckCircle2, PlayCircle } from 'lucide-react';

type Formation = { id: number; titre: string; statut: string };

const STATUT_LABEL: Record<string, { label: string; classe: string }> = {
    non_commencee: { label: 'À commencer', classe: 'bg-slate-100 text-slate-600' },
    en_cours: { label: 'En cours', classe: 'bg-blue-100 text-blue-700' },
    terminee: { label: 'Terminée', classe: 'bg-green-100 text-green-700' },
};

export default function Apprenant({
    stats,
    formations,
}: {
    stats: {
        inscriptions: number;
        en_cours: number;
        terminees: number;
        certificats: number;
    };
    formations: Formation[];
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

            <section className="mt-8 rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
                <h2 className="mb-4 text-lg font-bold">Mes formations</h2>

                {formations.length === 0 ? (
                    <p className="rounded-xl bg-slate-50 p-6 text-center text-sm text-slate-500">
                        Vous n'êtes inscrit à aucune formation pour le moment.
                        Votre superviseur vous inscrira prochainement.
                    </p>
                ) : (
                    <ul className="divide-y divide-slate-100">
                        {formations.map((f) => {
                            const s = STATUT_LABEL[f.statut] ?? STATUT_LABEL.non_commencee;
                            return (
                                <li
                                    key={f.id}
                                    className="flex items-center justify-between py-3"
                                >
                                    <span className="font-semibold text-[#1B2430]">
                                        {f.titre}
                                    </span>
                                    <span
                                        className={`rounded-full px-3 py-1 text-xs font-bold ${s.classe}`}
                                    >
                                        {s.label}
                                    </span>
                                </li>
                            );
                        })}
                    </ul>
                )}
            </section>
        </AuthenticatedLayout>
    );
}
