import { AuthButton, AuthField } from '@/Components/AuthField';
import InputError from '@/Components/InputError';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, GraduationCap } from 'lucide-react';
import { FormEventHandler } from 'react';

type Apprenant = { id: number; nom: string; domaine: string | null };
type Formation = { id: number; titre: string; type: string };

export default function Create({
    apprenants,
    formations,
}: {
    apprenants: Apprenant[];
    formations: Formation[];
}) {
    const { data, setData, post, processing, errors } = useForm({
        utilisateur_id: '',
        formation_id: '',
        objectif_quotidien: 3,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('affectations.store'));
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
                        <GraduationCap className="h-6 w-6 text-[#E23744]" />
                        Affecter une formation
                    </h1>
                    <p className="mt-1 text-sm text-slate-500">
                        Inscrivez un apprenant précis à une formation et fixez son
                        objectif quotidien (leçons à compléter par jour).
                    </p>
                </div>
            }
        >
            <Head title="Affecter une formation" />

            <div className="max-w-2xl rounded-2xl border border-slate-100 bg-white p-6 shadow-sm sm:p-8">
                {apprenants.length === 0 ? (
                    <p className="rounded-xl bg-slate-50 p-6 text-center text-sm text-slate-500">
                        Aucun apprenant à affecter pour le moment. Créez d'abord un
                        compte apprenant depuis « Comptes ».
                    </p>
                ) : (
                    <form onSubmit={submit} className="space-y-5">
                        {/* Apprenant */}
                        <div>
                            <label
                                htmlFor="utilisateur_id"
                                className="mb-1.5 block text-sm font-semibold text-[#1B2430]"
                            >
                                Apprenant
                            </label>
                            <select
                                id="utilisateur_id"
                                value={data.utilisateur_id}
                                onChange={(e) =>
                                    setData('utilisateur_id', e.target.value)
                                }
                                className={selectClasse}
                            >
                                <option value="">— Choisir un apprenant —</option>
                                {apprenants.map((a) => (
                                    <option key={a.id} value={a.id}>
                                        {a.nom}
                                        {a.domaine ? ` — ${a.domaine}` : ''}
                                    </option>
                                ))}
                            </select>
                            <InputError
                                message={errors.utilisateur_id}
                                className="mt-1.5"
                            />
                        </div>

                        {/* Formation */}
                        <div>
                            <label
                                htmlFor="formation_id"
                                className="mb-1.5 block text-sm font-semibold text-[#1B2430]"
                            >
                                Formation
                            </label>
                            <select
                                id="formation_id"
                                value={data.formation_id}
                                onChange={(e) =>
                                    setData('formation_id', e.target.value)
                                }
                                className={selectClasse}
                            >
                                <option value="">— Choisir une formation —</option>
                                {formations.map((f) => (
                                    <option key={f.id} value={f.id}>
                                        {f.titre}
                                        {f.type === 'certifiante'
                                            ? ' (certifiante)'
                                            : ''}
                                    </option>
                                ))}
                            </select>
                            <InputError
                                message={errors.formation_id}
                                className="mt-1.5"
                            />
                        </div>

                        {/* Objectif quotidien */}
                        <AuthField
                            id="objectif_quotidien"
                            label="Objectif quotidien (leçons / jour)"
                            type="number"
                            min={1}
                            max={20}
                            value={data.objectif_quotidien}
                            error={errors.objectif_quotidien}
                            onChange={(e) =>
                                setData(
                                    'objectif_quotidien',
                                    Number(e.target.value),
                                )
                            }
                        />

                        <div className="flex justify-end pt-2">
                            <AuthButton disabled={processing} className="w-auto">
                                <GraduationCap className="h-4 w-4" />
                                Affecter la formation
                            </AuthButton>
                        </div>
                    </form>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
