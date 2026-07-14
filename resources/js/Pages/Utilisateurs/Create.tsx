import { AuthButton, AuthField } from '@/Components/AuthField';
import InputError from '@/Components/InputError';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, UserPlus } from 'lucide-react';
import { FormEventHandler } from 'react';

type Superviseur = { id: number; nom_complet: string };
type RoleGerable = 'superviseur' | 'formateur' | 'apprenant';

const ROLE_LABEL: Record<RoleGerable, string> = {
    superviseur: 'Superviseur',
    formateur: 'Formateur',
    apprenant: 'Apprenant',
};

export default function Create({
    rolesDisponibles,
    superviseurs,
}: {
    rolesDisponibles: RoleGerable[];
    superviseurs: Superviseur[];
}) {
    const { data, setData, post, processing, errors } = useForm({
        nom_complet: '',
        email: '',
        // Le premier rôle proposé dépend du niveau hiérarchique de l'utilisateur.
        role: rolesDisponibles.includes('apprenant')
            ? 'apprenant'
            : rolesDisponibles[0],
        domaine: '',
        superviseur_id: '',
        password: '',
        password_confirmation: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('utilisateurs.store'));
    };

    return (
        <AuthenticatedLayout
            header={
                <div>
                    <Link
                        href={route('utilisateurs.index')}
                        className="mb-2 inline-flex items-center gap-1 text-sm font-semibold text-[#1C9AD6] hover:underline"
                    >
                        <ArrowLeft className="h-4 w-4" />
                        Retour aux comptes
                    </Link>
                    <h1 className="flex items-center gap-2 text-2xl font-extrabold">
                        <UserPlus className="h-6 w-6 text-[#E23744]" />
                        Nouveau compte
                    </h1>
                    <p className="mt-1 text-sm text-slate-500">
                        Créez un compte{' '}
                        {rolesDisponibles.map((r) => ROLE_LABEL[r].toLowerCase()).join(', ')}
                        . L'intéressé se connectera avec l'e-mail et le mot de
                        passe définis ici.
                    </p>
                </div>
            }
        >
            <Head title="Nouveau compte" />

            <div className="max-w-2xl rounded-2xl border border-slate-100 bg-white p-6 shadow-sm sm:p-8">
                <form onSubmit={submit} className="space-y-5">
                    {/* Rôle */}
                    <div>
                        <span className="mb-1.5 block text-sm font-semibold text-[#1B2430]">
                            Type de compte
                        </span>
                        <div
                            className={`grid gap-3 ${rolesDisponibles.length >= 3 ? 'grid-cols-3' : 'grid-cols-2'}`}
                        >
                            {rolesDisponibles.map((r) => (
                                <button
                                    key={r}
                                    type="button"
                                    onClick={() => setData('role', r)}
                                    className={`rounded-xl border-2 px-4 py-3 text-sm font-bold transition ${
                                        data.role === r
                                            ? 'border-[#E23744] bg-[#E23744]/5 text-[#E23744]'
                                            : 'border-slate-200 text-slate-500 hover:border-slate-300'
                                    }`}
                                >
                                    {ROLE_LABEL[r]}
                                </button>
                            ))}
                        </div>
                        <InputError message={errors.role} className="mt-1.5" />
                    </div>

                    <AuthField
                        id="nom_complet"
                        label="Nom complet"
                        type="text"
                        value={data.nom_complet}
                        autoFocus
                        placeholder="Prénom Nom"
                        error={errors.nom_complet}
                        onChange={(e) => setData('nom_complet', e.target.value)}
                    />

                    <AuthField
                        id="email"
                        label="Adresse e-mail"
                        type="email"
                        value={data.email}
                        placeholder="prenom.nom@triumph.com"
                        error={errors.email}
                        onChange={(e) => setData('email', e.target.value)}
                    />

                    <AuthField
                        id="domaine"
                        label="Atelier / domaine (facultatif)"
                        type="text"
                        value={data.domaine}
                        placeholder="Couture, Coupe, Qualité…"
                        error={errors.domaine}
                        onChange={(e) => setData('domaine', e.target.value)}
                    />

                    {/* Superviseur — uniquement pour les apprenants */}
                    {data.role === 'apprenant' && (
                        <div>
                            <label
                                htmlFor="superviseur_id"
                                className="mb-1.5 block text-sm font-semibold text-[#1B2430]"
                            >
                                Superviseur rattaché (facultatif)
                            </label>
                            <select
                                id="superviseur_id"
                                value={data.superviseur_id}
                                onChange={(e) =>
                                    setData('superviseur_id', e.target.value)
                                }
                                className="block w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-[#1B2430] shadow-sm focus:border-[#1C9AD6] focus:outline-none focus:ring-2 focus:ring-[#1C9AD6]/30"
                            >
                                <option value="">— Aucun —</option>
                                {superviseurs.map((s) => (
                                    <option key={s.id} value={s.id}>
                                        {s.nom_complet}
                                    </option>
                                ))}
                            </select>
                            <InputError
                                message={errors.superviseur_id}
                                className="mt-1.5"
                            />
                        </div>
                    )}

                    <div className="grid gap-5 sm:grid-cols-2">
                        <AuthField
                            id="password"
                            label="Mot de passe"
                            type="password"
                            value={data.password}
                            autoComplete="new-password"
                            placeholder="••••••••"
                            error={errors.password}
                            onChange={(e) => setData('password', e.target.value)}
                        />
                        <AuthField
                            id="password_confirmation"
                            label="Confirmer"
                            type="password"
                            value={data.password_confirmation}
                            autoComplete="new-password"
                            placeholder="••••••••"
                            onChange={(e) =>
                                setData('password_confirmation', e.target.value)
                            }
                        />
                    </div>

                    <div className="flex justify-end pt-2">
                        <AuthButton disabled={processing} className="w-auto">
                            <UserPlus className="h-4 w-4" />
                            Créer le compte
                        </AuthButton>
                    </div>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}
