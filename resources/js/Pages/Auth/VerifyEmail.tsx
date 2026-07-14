import { AuthButton } from '@/Components/AuthField';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { MailCheck } from 'lucide-react';
import { FormEventHandler } from 'react';

export default function VerifyEmail({ status }: { status?: string }) {
    const { post, processing } = useForm({});

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('verification.send'));
    };

    return (
        <GuestLayout
            title="Vérification de l'e-mail"
            subtitle="Confirmez votre adresse pour continuer."
        >
            <Head title="Vérification de l'e-mail" />

            <p className="mb-4 text-sm leading-relaxed text-slate-600">
                Merci ! Avant de commencer, veuillez vérifier votre adresse
                e-mail en cliquant sur le lien que nous venons de vous envoyer.
                Vous ne l'avez pas reçu ? Nous pouvons vous en renvoyer un.
            </p>

            {status === 'verification-link-sent' && (
                <div className="mb-4 rounded-lg bg-green-50 px-4 py-3 text-sm font-medium text-green-700">
                    Un nouveau lien de vérification vient d'être envoyé à votre
                    adresse e-mail.
                </div>
            )}

            <form onSubmit={submit}>
                <AuthButton disabled={processing}>
                    <MailCheck className="h-4 w-4" />
                    Renvoyer l'e-mail de vérification
                </AuthButton>
            </form>

            <Link
                href={route('logout')}
                method="post"
                as="button"
                className="mt-6 text-sm font-semibold text-[#1C9AD6] hover:underline"
            >
                Se déconnecter
            </Link>
        </GuestLayout>
    );
}
