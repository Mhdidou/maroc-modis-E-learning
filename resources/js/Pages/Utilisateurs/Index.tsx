import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { CheckCircle2, UserCog, UserPlus, UserX, UserCheck } from 'lucide-react';

type Utilisateur = {
    id: number;
    nom: string;
    email: string;
    role: 'superviseur' | 'formateur' | 'apprenant';
    domaine: string | null;
    superviseur: string | null;
    cree_le: string | null;
    actif: boolean;
    desactive_le: string | null;
};

const ROLE_BADGE: Record<string, string> = {
    superviseur: 'bg-indigo-100 text-indigo-700',
    formateur: 'bg-blue-100 text-blue-700',
    apprenant: 'bg-red-100 text-red-700',
};

export default function Index({
    utilisateurs,
}: {
    utilisateurs: Utilisateur[];
}) {
    const flash = usePage().props.flash;

    const roleBadge = (role: string) =>
        ROLE_BADGE[role] ?? 'bg-slate-100 text-slate-600';

    // Désactivation, jamais suppression : l'historique de formation d'un employé
    // (inscriptions, progression, certificats) est une pièce d'audit conservée.
    const desactiver = (u: Utilisateur) => {
        if (
            !window.confirm(
                `Désactiver le compte de ${u.nom} ?\n\n` +
                    "L'accès à la plateforme est retiré immédiatement. " +
                    'Son historique de formation et ses certificats sont conservés, ' +
                    'et le compte peut être réactivé à tout moment.',
            )
        ) {
            return;
        }

        router.delete(route('utilisateurs.destroy', u.id), {
            preserveScroll: true,
        });
    };

    const reactiver = (u: Utilisateur) => {
        router.post(route('utilisateurs.restore', u.id), undefined, {
            preserveScroll: true,
        });
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h1 className="flex items-center gap-2 text-2xl font-extrabold">
                            <UserCog className="h-6 w-6 text-[#1C9AD6]" />
                            Gestion des comptes
                        </h1>
                        <p className="mt-1 text-sm text-slate-500">
                            Formateurs et apprenants de la plateforme.
                        </p>
                    </div>
                    <Link
                        href={route('utilisateurs.create')}
                        className="inline-flex items-center gap-2 rounded-xl bg-[#E23744] px-5 py-3 text-sm font-bold text-white shadow-lg shadow-[#E23744]/25 transition active:scale-95 hover:brightness-110"
                    >
                        <UserPlus className="h-4 w-4" />
                        Nouveau compte
                    </Link>
                </div>
            }
        >
            <Head title="Gestion des comptes" />

            {flash?.status && (
                <div className="mb-6 flex items-center gap-2 rounded-xl bg-green-50 px-4 py-3 text-sm font-medium text-green-700">
                    <CheckCircle2 className="h-4 w-4" />
                    {flash.status}
                </div>
            )}

            <div className="overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-sm">
                <div className="overflow-x-auto">
                    <table className="w-full text-left text-sm">
                        <thead>
                            <tr className="border-b border-slate-100 bg-slate-50 text-xs uppercase tracking-wide text-slate-400">
                                <th className="px-5 py-3 font-semibold">Nom</th>
                                <th className="px-5 py-3 font-semibold">E-mail</th>
                                <th className="px-5 py-3 font-semibold">Rôle</th>
                                <th className="px-5 py-3 font-semibold">Atelier</th>
                                <th className="px-5 py-3 font-semibold">Superviseur</th>
                                <th className="px-5 py-3 font-semibold">Créé le</th>
                                <th className="px-5 py-3 font-semibold">Statut</th>
                                <th className="px-5 py-3 text-right font-semibold">
                                    Action
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {utilisateurs.length === 0 ? (
                                <tr>
                                    <td
                                        colSpan={8}
                                        className="px-5 py-8 text-center text-slate-500"
                                    >
                                        Aucun compte pour le moment.
                                    </td>
                                </tr>
                            ) : (
                                utilisateurs.map((u) => (
                                    <tr
                                        key={u.id}
                                        className={`hover:bg-slate-50 ${u.actif ? '' : 'bg-slate-50/60 text-slate-400'}`}
                                    >
                                        <td
                                            className={`px-5 py-3 font-semibold ${u.actif ? 'text-[#1B2430]' : 'text-slate-400 line-through'}`}
                                        >
                                            {u.nom}
                                        </td>
                                        <td className="px-5 py-3 text-slate-600">
                                            {u.email}
                                        </td>
                                        <td className="px-5 py-3">
                                            <span
                                                className={`rounded-full px-2.5 py-0.5 text-xs font-bold capitalize ${roleBadge(u.role)}`}
                                            >
                                                {u.role}
                                            </span>
                                        </td>
                                        <td className="px-5 py-3 text-slate-600">
                                            {u.domaine ?? '—'}
                                        </td>
                                        <td className="px-5 py-3 text-slate-600">
                                            {u.superviseur ?? '—'}
                                        </td>
                                        <td className="px-5 py-3 text-slate-500">
                                            {u.cree_le ?? '—'}
                                        </td>
                                        <td className="px-5 py-3">
                                            {u.actif ? (
                                                <span className="rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-bold text-green-700">
                                                    Actif
                                                </span>
                                            ) : (
                                                <span
                                                    className="rounded-full bg-slate-200 px-2.5 py-0.5 text-xs font-bold text-slate-600"
                                                    title={`Désactivé le ${u.desactive_le ?? '—'}`}
                                                >
                                                    Désactivé
                                                </span>
                                            )}
                                        </td>
                                        <td className="px-5 py-3 text-right">
                                            {u.actif ? (
                                                <button
                                                    type="button"
                                                    onClick={() => desactiver(u)}
                                                    className="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-bold text-slate-500 transition hover:bg-red-50 hover:text-[#E23744]"
                                                >
                                                    <UserX className="h-3.5 w-3.5" />
                                                    Désactiver
                                                </button>
                                            ) : (
                                                <button
                                                    type="button"
                                                    onClick={() => reactiver(u)}
                                                    className="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-bold text-slate-500 transition hover:bg-green-50 hover:text-green-700"
                                                >
                                                    <UserCheck className="h-3.5 w-3.5" />
                                                    Réactiver
                                                </button>
                                            )}
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
