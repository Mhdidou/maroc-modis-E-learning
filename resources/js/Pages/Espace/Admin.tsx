import { BRAND } from '@/brand';
import StatCard from '@/Components/StatCard';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, usePage } from '@inertiajs/react';
import {
    Award,
    BookOpen,
    GraduationCap,
    ShieldCheck,
    UserPlus,
    Users,
} from 'lucide-react';

const ADMIN_ACCENT = '#4338CA';

type Superviseur = {
    id: number;
    nom: string;
    email: string;
    domaine: string | null;
    apprenants: number;
};

const STATUT_LABEL: Record<string, string> = {
    non_commencee: 'Non commencées',
    en_cours: 'En cours',
    terminee: 'Terminées',
};

export default function Admin({
    stats,
    repartitionStatuts,
    superviseurs,
}: {
    stats: {
        superviseurs: number;
        formateurs: number;
        apprenants: number;
        formations: number;
        certificats: number;
        certificats_expires: number;
    };
    repartitionStatuts: Record<string, number>;
    superviseurs: Superviseur[];
}) {
    const user = usePage().props.auth.user;

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h1 className="flex items-center gap-2 text-2xl font-extrabold">
                            <ShieldCheck
                                className="h-6 w-6"
                                style={{ color: ADMIN_ACCENT }}
                            />
                            Espace Administration
                        </h1>
                        <p className="mt-1 text-sm text-slate-500">
                            {user.name} · rôle supérieur — vous supervisez les
                            superviseurs et l'ensemble de la plateforme.
                        </p>
                    </div>
                    <Link
                        href={route('utilisateurs.create')}
                        className="inline-flex items-center gap-2 rounded-xl px-5 py-3 text-sm font-bold text-white shadow-lg transition active:scale-95 hover:brightness-110"
                        style={{
                            backgroundColor: ADMIN_ACCENT,
                            boxShadow: `0 10px 15px -3px ${ADMIN_ACCENT}40`,
                        }}
                    >
                        <UserPlus className="h-4 w-4" />
                        Ajouter un compte
                    </Link>
                </div>
            }
        >
            <Head title="Espace Administration" />

            {/* Bandeau hiérarchie */}
            <div
                className="mb-6 flex items-center gap-3 rounded-2xl border p-4 text-sm"
                style={{
                    borderColor: `${ADMIN_ACCENT}33`,
                    backgroundColor: `${ADMIN_ACCENT}0D`,
                }}
            >
                <ShieldCheck
                    className="h-5 w-5 shrink-0"
                    style={{ color: ADMIN_ACCENT }}
                />
                <p className="text-slate-600">
                    <strong className="text-[#1B2430]">
                        Administrateur du site
                    </strong>{' '}
                    — niveau d'accès le plus élevé. Vous seul pouvez créer et
                    gérer les comptes <strong>superviseurs</strong>, en plus des
                    formateurs et apprenants.
                </p>
            </div>

            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                <StatCard
                    icon={ShieldCheck}
                    label="Superviseurs"
                    value={stats.superviseurs}
                    accent={ADMIN_ACCENT}
                />
                <StatCard
                    icon={Users}
                    label="Formateurs"
                    value={stats.formateurs}
                    accent={BRAND.blue}
                />
                <StatCard
                    icon={GraduationCap}
                    label="Apprenants"
                    value={stats.apprenants}
                    accent={BRAND.red}
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

            {/* Avancement global */}
            <section className="mt-8 rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
                <h2 className="mb-4 text-lg font-bold">
                    Avancement global des inscriptions
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

            {/* Supervision des superviseurs */}
            <section className="mt-8 rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
                <div className="mb-4 flex items-center justify-between">
                    <h2 className="text-lg font-bold">
                        Superviseurs de la plateforme
                    </h2>
                    <Link
                        href={route('utilisateurs.index')}
                        className="text-sm font-semibold hover:underline"
                        style={{ color: ADMIN_ACCENT }}
                    >
                        Gérer tous les comptes →
                    </Link>
                </div>

                {superviseurs.length === 0 ? (
                    <p className="rounded-xl bg-slate-50 p-6 text-center text-sm text-slate-500">
                        Aucun superviseur pour le moment. Créez-en un via
                        « Ajouter un compte ».
                    </p>
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead>
                                <tr className="border-b border-slate-100 text-xs uppercase tracking-wide text-slate-400">
                                    <th className="py-2 pr-4 font-semibold">Nom</th>
                                    <th className="py-2 pr-4 font-semibold">E-mail</th>
                                    <th className="py-2 pr-4 font-semibold">Atelier</th>
                                    <th className="py-2 font-semibold">Apprenants</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {superviseurs.map((s) => (
                                    <tr key={s.id}>
                                        <td className="py-3 pr-4 font-semibold text-[#1B2430]">
                                            {s.nom}
                                        </td>
                                        <td className="py-3 pr-4 text-slate-600">
                                            {s.email}
                                        </td>
                                        <td className="py-3 pr-4 text-slate-600">
                                            {s.domaine ?? '—'}
                                        </td>
                                        <td className="py-3">
                                            <span className="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-bold text-slate-700">
                                                {s.apprenants}
                                            </span>
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
