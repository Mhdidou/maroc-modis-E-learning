import { AuthButton, AuthField } from '@/Components/AuthField';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, useForm } from '@inertiajs/react';
import { KeyRound } from 'lucide-react';
import { FormEventHandler } from 'react';

export default function ResetPassword({
    token,
    email,
}: {
    token: string;
    email: string;
}) {
    const { data, setData, post, processing, errors, reset } = useForm({
        token: token,
        email: email,
        password: '',
        password_confirmation: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('password.store'), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <GuestLayout
            title="Nouveau mot de passe"
            subtitle="Choisissez un mot de passe sécurisé."
        >
            <Head title="Réinitialisation" />

            <form onSubmit={submit} className="space-y-4">
                <AuthField
                    id="email"
                    label="Adresse e-mail"
                    type="email"
                    name="email"
                    value={data.email}
                    autoComplete="username"
                    error={errors.email}
                    onChange={(e) => setData('email', e.target.value)}
                />

                <AuthField
                    id="password"
                    label="Nouveau mot de passe"
                    type="password"
                    name="password"
                    value={data.password}
                    autoComplete="new-password"
                    autoFocus
                    placeholder="••••••••"
                    error={errors.password}
                    onChange={(e) => setData('password', e.target.value)}
                />

                <AuthField
                    id="password_confirmation"
                    label="Confirmer le mot de passe"
                    type="password"
                    name="password_confirmation"
                    value={data.password_confirmation}
                    autoComplete="new-password"
                    placeholder="••••••••"
                    error={errors.password_confirmation}
                    onChange={(e) =>
                        setData('password_confirmation', e.target.value)
                    }
                />

                <AuthButton disabled={processing}>
                    <KeyRound className="h-4 w-4" />
                    Réinitialiser le mot de passe
                </AuthButton>
            </form>
        </GuestLayout>
    );
}
