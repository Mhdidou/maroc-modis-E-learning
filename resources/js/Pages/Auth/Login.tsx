import { AuthButton, AuthField } from '@/Components/AuthField';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { LogIn } from 'lucide-react';
import { FormEventHandler } from 'react';

export default function Login({
    status,
    canResetPassword,
}: {
    status?: string;
    canResetPassword: boolean;
}) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false as boolean,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <GuestLayout
            title="Connexion au portail"
            subtitle="Accédez à votre espace de formation Maroc-Modis."
        >
            <Head title="Connexion" />

            {status && (
                <div className="mb-4 rounded-lg bg-green-50 px-4 py-3 text-sm font-medium text-green-700">
                    {status}
                </div>
            )}

            <form onSubmit={submit} className="space-y-4">
                <AuthField
                    id="email"
                    label="Adresse e-mail"
                    type="email"
                    name="email"
                    value={data.email}
                    autoComplete="username"
                    autoFocus
                    placeholder="prenom.nom@triumph.com"
                    error={errors.email}
                    onChange={(e) => setData('email', e.target.value)}
                />

                <AuthField
                    id="password"
                    label="Mot de passe"
                    type="password"
                    name="password"
                    value={data.password}
                    autoComplete="current-password"
                    placeholder="••••••••"
                    error={errors.password}
                    onChange={(e) => setData('password', e.target.value)}
                />

                <div className="flex items-center justify-between">
                    <label className="flex items-center gap-2 text-sm text-slate-600">
                        <input
                            type="checkbox"
                            name="remember"
                            checked={data.remember}
                            onChange={(e) =>
                                setData('remember', e.target.checked)
                            }
                            className="rounded border-slate-300 text-[#E23744] focus:ring-[#1C9AD6]/40"
                        />
                        Se souvenir de moi
                    </label>

                    {canResetPassword && (
                        <Link
                            href={route('password.request')}
                            className="text-sm font-semibold text-[#1C9AD6] hover:underline"
                        >
                            Mot de passe oublié ?
                        </Link>
                    )}
                </div>

                <AuthButton disabled={processing}>
                    <LogIn className="h-4 w-4" />
                    Se connecter
                </AuthButton>
            </form>

            <p className="mt-6 text-center text-xs text-slate-500">
                Pas encore de compte ? Rapprochez-vous de votre superviseur ou
                de l'administrateur du site.
            </p>
        </GuestLayout>
    );
}
