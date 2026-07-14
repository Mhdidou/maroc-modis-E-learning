import { AuthButton, AuthField } from '@/Components/AuthField';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Mail } from 'lucide-react';
import { FormEventHandler } from 'react';

export default function ForgotPassword({ status }: { status?: string }) {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('password.email'));
    };

    return (
        <GuestLayout
            title="Mot de passe oublié"
            subtitle="Nous vous enverrons un lien de réinitialisation."
        >
            <Head title="Mot de passe oublié" />

            <p className="mb-4 text-sm leading-relaxed text-slate-600">
                Saisissez votre adresse e-mail : vous recevrez un lien vous
                permettant de choisir un nouveau mot de passe.
            </p>

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
                    autoFocus
                    placeholder="prenom.nom@triumph.com"
                    error={errors.email}
                    onChange={(e) => setData('email', e.target.value)}
                />

                <AuthButton disabled={processing}>
                    <Mail className="h-4 w-4" />
                    Envoyer le lien
                </AuthButton>
            </form>

            <Link
                href={route('login')}
                className="mt-6 inline-flex items-center gap-2 text-sm font-semibold text-[#1C9AD6] hover:underline"
            >
                <ArrowLeft className="h-4 w-4" />
                Retour à la connexion
            </Link>
        </GuestLayout>
    );
}
