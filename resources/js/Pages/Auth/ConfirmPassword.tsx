import { AuthButton, AuthField } from '@/Components/AuthField';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, useForm } from '@inertiajs/react';
import { ShieldCheck } from 'lucide-react';
import { FormEventHandler } from 'react';

export default function ConfirmPassword() {
    const { data, setData, post, processing, errors, reset } = useForm({
        password: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('password.confirm'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <GuestLayout
            title="Zone sécurisée"
            subtitle="Confirmez votre mot de passe pour continuer."
        >
            <Head title="Confirmation" />

            <p className="mb-4 text-sm leading-relaxed text-slate-600">
                Cette section est protégée. Merci de confirmer votre mot de
                passe avant de poursuivre.
            </p>

            <form onSubmit={submit} className="space-y-4">
                <AuthField
                    id="password"
                    label="Mot de passe"
                    type="password"
                    name="password"
                    value={data.password}
                    autoFocus
                    placeholder="••••••••"
                    error={errors.password}
                    onChange={(e) => setData('password', e.target.value)}
                />

                <AuthButton disabled={processing}>
                    <ShieldCheck className="h-4 w-4" />
                    Confirmer
                </AuthButton>
            </form>
        </GuestLayout>
    );
}
