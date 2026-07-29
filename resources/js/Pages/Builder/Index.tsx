import InputError from '@/Components/InputError';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Layers, Pencil, Plus, UserCheck } from 'lucide-react';
import { FormEventHandler } from 'react';
import { FormationStatut, FormationType } from './types';

type FormationRow = {
    id: number;
    titre: string;
    type: FormationType;
    statut: FormationStatut;
    chapitres_count: number;
    responsable: string | null;
    attribuee: boolean;
};

export type Responsable = { id: number; nom: string; role: string };

export default function Index({
    formations,
    responsables,
}: {
    formations: { data: FormationRow[] };
    /** Fourni uniquement à l'admin : il saisit pour le compte d'un tiers. */
    responsables: Responsable[] | null;
}) {
    const estAdmin = responsables !== null;

    const { data, setData, post, processing, errors, reset } = useForm({
        titre: '',
        type: 'non_certifiante' as FormationType,
        description: '',
        validite_mois: '' as number | '',
        cree_par: '' as number | '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('builder.formations.store'), { onSuccess: () => reset() });
    };

    const selectClasse =
        'block w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-[#1B2430] shadow-sm focus:border-[#1C9AD6] focus:outline-none focus:ring-2 focus:ring-[#1C9AD6]/30';

    return (
        <AuthenticatedLayout
            header={
                <div>
                    <Link
                        href={route('dashboard')}
                        className="mb-2 inline-flex items-center gap-1 text-sm font-semibold text-[#1C9AD6] hover:underline"
                    >
                        <ArrowLeft className="h-4 w-4" />
                        Retour au tableau de bord
                    </Link>
                    <h1 className="flex items-center gap-2 text-2xl font-extrabold">
                        <Layers className="h-6 w-6 text-[#E23744]" />
                        Atelier de formation
                    </h1>
                    <p className="mt-1 text-sm text-slate-500">
                        Construisez vos cours : modules, leçons, quiz et
                        devoirs, en glisser-déposer.
                    </p>
                </div>
            }
        >
            <Head title="Atelier de formation" />

            <div className="grid gap-6 lg:grid-cols-3">
                {/* Création */}
                <section className="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
                    <h2 className="mb-4 flex items-center gap-2 text-lg font-bold">
                        <Plus className="h-5 w-5 text-[#1C9AD6]" />
                        Nouvelle formation
                    </h2>
                    <form onSubmit={submit} className="space-y-4">
                        <div>
                            <label className="mb-1.5 block text-sm font-semibold">
                                Titre
                            </label>
                            <input
                                type="text"
                                value={data.titre}
                                onChange={(e) => setData('titre', e.target.value)}
                                className={selectClasse}
                                placeholder="Ex : Couture — Points de base"
                            />
                            <InputError message={errors.titre} className="mt-1.5" />
                        </div>

                        {/* L'admin n'est pas auteur : il désigne obligatoirement
                            le responsable pédagogique de la formation. */}
                        {estAdmin && (
                            <div>
                                <label className="mb-1.5 block text-sm font-semibold">
                                    Responsable de la formation
                                </label>
                                <select
                                    value={data.cree_par}
                                    onChange={(e) =>
                                        setData(
                                            'cree_par',
                                            e.target.value === ''
                                                ? ''
                                                : Number(e.target.value),
                                        )
                                    }
                                    className={selectClasse}
                                >
                                    <option value="">
                                        — Choisir un formateur ou superviseur —
                                    </option>
                                    {responsables.map((r) => (
                                        <option key={r.id} value={r.id}>
                                            {r.nom} ({r.role})
                                        </option>
                                    ))}
                                </select>
                                <p className="mt-1.5 text-xs text-slate-500">
                                    La formation lui sera attribuée : c'est son
                                    nom qui figurera sur les certificats.
                                </p>
                                <InputError
                                    message={errors.cree_par}
                                    className="mt-1.5"
                                />
                            </div>
                        )}

                        <div>
                            <label className="mb-1.5 block text-sm font-semibold">
                                Type
                            </label>
                            <select
                                value={data.type}
                                onChange={(e) =>
                                    setData('type', e.target.value as FormationType)
                                }
                                className={selectClasse}
                            >
                                <option value="non_certifiante">
                                    Non certifiante
                                </option>
                                <option value="certifiante">Certifiante</option>
                            </select>
                        </div>
                        {data.type === 'certifiante' && (
                            <div>
                                <label className="mb-1.5 block text-sm font-semibold">
                                    Validité du certificat (mois)
                                </label>
                                <input
                                    type="number"
                                    min={1}
                                    max={120}
                                    value={data.validite_mois}
                                    onChange={(e) =>
                                        setData(
                                            'validite_mois',
                                            e.target.value === ''
                                                ? ''
                                                : Number(e.target.value),
                                        )
                                    }
                                    className={selectClasse}
                                    placeholder="24"
                                />
                                <InputError
                                    message={errors.validite_mois}
                                    className="mt-1.5"
                                />
                            </div>
                        )}
                        <button
                            type="submit"
                            disabled={processing}
                            className="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-[#E23744] px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:brightness-95 disabled:opacity-60"
                        >
                            <Plus className="h-4 w-4" />
                            Créer et ouvrir l'atelier
                        </button>
                    </form>
                </section>

                {/* Liste */}
                <section className="lg:col-span-2">
                    {formations.data.length === 0 ? (
                        <div className="rounded-2xl border border-dashed border-slate-200 bg-white p-10 text-center text-sm text-slate-500">
                            Aucune formation pour le moment. Créez-en une pour
                            commencer.
                        </div>
                    ) : (
                        <div className="grid gap-4 sm:grid-cols-2">
                            {formations.data.map((f) => (
                                <Link
                                    key={f.id}
                                    href={route('builder.show', f.id)}
                                    className="group rounded-2xl border border-slate-100 bg-white p-5 shadow-sm transition hover:border-[#1C9AD6] hover:shadow-md"
                                >
                                    <div className="mb-3 flex items-center justify-between">
                                        <span
                                            className={`rounded-full px-2.5 py-0.5 text-xs font-bold ${
                                                f.statut === 'publie'
                                                    ? 'bg-green-100 text-green-700'
                                                    : 'bg-amber-100 text-amber-700'
                                            }`}
                                        >
                                            {f.statut === 'publie'
                                                ? 'Publiée'
                                                : 'Brouillon'}
                                        </span>
                                        <Pencil className="h-4 w-4 text-slate-300 group-hover:text-[#1C9AD6]" />
                                    </div>
                                    <h3 className="text-base font-bold text-[#1B2430]">
                                        {f.titre}
                                    </h3>
                                    <p className="mt-2 text-xs text-slate-500">
                                        {f.type === 'certifiante'
                                            ? 'Certifiante'
                                            : 'Non certifiante'}{' '}
                                        · {f.chapitres_count} module
                                        {f.chapitres_count > 1 ? 's' : ''}
                                    </p>
                                    {estAdmin && (
                                        <p
                                            className={`mt-2 flex items-center gap-1.5 text-xs font-semibold ${
                                                f.attribuee
                                                    ? 'text-slate-600'
                                                    : 'text-[#E23744]'
                                            }`}
                                        >
                                            <UserCheck className="h-3.5 w-3.5" />
                                            {f.attribuee
                                                ? f.responsable
                                                : 'Non attribuée'}
                                        </p>
                                    )}
                                </Link>
                            ))}
                        </div>
                    )}
                </section>
            </div>
        </AuthenticatedLayout>
    );
}
