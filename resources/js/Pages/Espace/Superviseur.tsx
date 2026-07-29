import ApercuBanner from '@/Components/ApercuBanner';
import { BRAND } from '@/brand';
import StatCard from '@/Components/StatCard';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, usePage } from '@inertiajs/react';
import { Award, BookOpen, GraduationCap, UserPlus, Users } from 'lucide-react';

type Apprenant = {
    id: number;
    nom: string;
    email: string;
    domaine: string | null;
};

const STATUT_LABEL: Record<string, string> = {
    non_commencee: 'Non commencées',
    en_cours: 'En cours',
    terminee: 'Terminées',
};

export default function Superviseur({
    stats,
    repartitionStatuts,
    apprenants,
    apercu,
}: {
    stats: {
        apprenants: number;
        formateurs: number;
        formations: number;
        certificats: number;
        certificats_expires: number;
    };
    repartitionStatuts: Record<string, number>;
    apprenants: Apprenant[];
    apercu?: string;
}) {
    const user = usePage().props.auth.user;

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-extrabold">
                            Espace Superviseur — {user.name}
                        </h1>
                        <p className="mt-1 text-sm text-slate-500">
                            Pilotez la montée en compétence de vos équipes.
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Link
                            href={route('affectations.create')}
                            className="inline-flex items-center gap-2 rounded-xl bg-[#1C9AD6] px-5 py-3 text-sm font-bold text-white shadow-lg shadow-[#1C9AD6]/25 transition active:scale-95 hover:brightness-110"
                        >
                            <GraduationCap className="h-4 w-4" />
                            Affecter une formation
                        </Link>
                        <Link
                            href={route('utilisateurs.create')}
                            className="inline-flex items-center gap-2 rounded-xl bg-[#E23744] px-5 py-3 text-sm font-bold text-white shadow-lg shadow-[#E23744]/25 transition active:scale-95 hover:brightness-110"
                        >
                            <UserPlus className="h-4 w-4" />
                            Ajouter un compte
                        </Link>
                    </div>
                </div>
            }
        >
            <Head title="Espace Superviseur" />

            {apercu && <ApercuBanner role="superviseur" />}

            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <StatCard
                    icon={GraduationCap}
                    label="Mes apprenants"
                    value={stats.apprenants}
                    accent={BRAND.red}
                />
                <StatCard
                    icon={Users}
                    label="Formateurs"
                    value={stats.formateurs}
                    accent={BRAND.blue}
                />
                <StatCard
                    icon={BookOpen}
                    label="Formations"
                    value={stats.formations}
                    accent={BRAND.ink}
                />
                <StatCard
                    icon={Award}
                    label={
                        stats.certificats_expires > 0
                            ? `Certificats valides · ${stats.certificats_expires} expiré${stats.certificats_expires > 1 ? 's' : ''}`
                            : 'Certificats valides'
                    }
                    value={stats.certificats}
                    accent="#16a34a"
                />
            </div>

            {/* Répartition des inscriptions par statut */}
            <section className="mt-8 rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
                <h2 className="mb-4 text-lg font-bold">
                    Avancement des inscriptions
                </h2>
                <div className="grid gap-4 sm:grid-cols-3">
                    {Object.entries(STATUT_LABEL).map(([key, label]) => (
                        <div
                            key={key}
                            className="rounded-xl bg-slate-50 p-4 text-center"
                        >
                            <div className="text-2xl font-extrabold text-[#1B2430]">
                                {repartitionStatuts[key] ?? 0}
                            </div>
                            <div className="mt-1 text-xs font-medium text-slate-500">
                                {label}
                            </div>
                        </div>
                    ))}
                </div>
            </section>

            {/* Liste des apprenants */}
            <section className="mt-8 rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
                <div className="mb-4 flex items-center justify-between">
                    <h2 className="text-lg font-bold">Mes apprenants</h2>
                    <Link
                        href={route('utilisateurs.index')}
                        className="text-sm font-semibold text-[#1C9AD6] hover:underline"
                    >
                        Gérer les comptes →
                    </Link>
                </div>

                {apprenants.length === 0 ? (
                    <p className="rounded-xl bg-slate-50 p-6 text-center text-sm text-slate-500">
                        Aucun apprenant rattaché pour le moment.
                    </p>
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead>
                                <tr className="border-b border-slate-100 text-xs uppercase tracking-wide text-slate-400">
                                    <th className="py-2 pr-4 font-semibold">Nom</th>
                                    <th className="py-2 pr-4 font-semibold">E-mail</th>
                                    <th className="py-2 font-semibold">Atelier</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {apprenants.map((a) => (
                                    <tr key={a.id}>
                                        <td className="py-3 pr-4 font-semibold text-[#1B2430]">
                                            {a.nom}
                                        </td>
                                        <td className="py-3 pr-4 text-slate-600">
                                            {a.email}
                                        </td>
                                        <td className="py-3 text-slate-600">
                                            {a.domaine ?? '—'}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </section>
        </AuthenticatedLayout>
    );
}
