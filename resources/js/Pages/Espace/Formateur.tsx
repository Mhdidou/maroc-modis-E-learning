import { BRAND } from '@/brand';
import StatCard from '@/Components/StatCard';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, usePage } from '@inertiajs/react';
import { BadgeCheck, BookOpen, Layers, Users } from 'lucide-react';

type Formation = {
    id: number;
    titre: string;
    type: string;
    modules: number;
    inscrits: number;
};

export default function Formateur({
    stats,
    formations,
}: {
    stats: {
        formations: number;
        modules: number;
        inscrits: number;
        certifiantes: number;
    };
    formations: Formation[];
}) {
    const user = usePage().props.auth.user;

    return (
        <AuthenticatedLayout
            header={
                <div>
                    <h1 className="text-2xl font-extrabold">
                        Espace Formateur — {user.name}
                    </h1>
                    <p className="mt-1 text-sm text-slate-500">
                        Créez et pilotez vos modules de formation
                        {user.domaine ? ` (${user.domaine})` : ''}.
                    </p>
                </div>
            }
        >
            <Head title="Espace Formateur" />

            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <StatCard
                    icon={BookOpen}
                    label="Formations créées"
                    value={stats.formations}
                    accent={BRAND.blue}
                />
                <StatCard
                    icon={Layers}
                    label="Modules publiés"
                    value={stats.modules}
                    accent={BRAND.red}
                />
                <StatCard
                    icon={Users}
                    label="Apprenants inscrits"
                    value={stats.inscrits}
                    accent={BRAND.ink}
                />
                <StatCard
                    icon={BadgeCheck}
                    label="Formations certifiantes"
                    value={stats.certifiantes}
                    accent="#16a34a"
                />
            </div>

            <section className="mt-8 rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
                <h2 className="mb-4 text-lg font-bold">Mes formations</h2>

                {formations.length === 0 ? (
                    <p className="rounded-xl bg-slate-50 p-6 text-center text-sm text-slate-500">
                        Vous n'avez pas encore créé de formation.
                    </p>
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead>
                                <tr className="border-b border-slate-100 text-xs uppercase tracking-wide text-slate-400">
                                    <th className="py-2 pr-4 font-semibold">Titre</th>
                                    <th className="py-2 pr-4 font-semibold">Type</th>
                                    <th className="py-2 pr-4 font-semibold">Modules</th>
                                    <th className="py-2 font-semibold">Inscrits</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {formations.map((f) => (
                                    <tr key={f.id}>
                                        <td className="py-3 pr-4 font-semibold text-[#1B2430]">
                                            {f.titre}
                                        </td>
                                        <td className="py-3 pr-4">
                                            <span
                                                className={`rounded-full px-2.5 py-0.5 text-xs font-bold ${
                                                    f.type === 'certifiante'
                                                        ? 'bg-green-100 text-green-700'
                                                        : 'bg-slate-100 text-slate-600'
                                                }`}
                                            >
                                                {f.type === 'certifiante'
                                                    ? 'Certifiante'
                                                    : 'Non certifiante'}
                                            </span>
                                        </td>
                                        <td className="py-3 pr-4 text-slate-600">
                                            {f.modules}
                                        </td>
                                        <td className="py-3 text-slate-600">
                                            {f.inscrits}
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
