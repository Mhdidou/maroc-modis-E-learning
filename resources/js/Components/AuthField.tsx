import InputError from '@/Components/InputError';
import { InputHTMLAttributes } from 'react';

/**
 * Champ de saisie brandé (label + input + erreur) pour les pages d'auth
 * et la gestion des comptes. Cohérent avec la charte Maroc-Modis.
 */
export function AuthField({
    label,
    error,
    className = '',
    ...props
}: InputHTMLAttributes<HTMLInputElement> & {
    label: string;
    error?: string;
}) {
    return (
        <div className={className}>
            <label
                htmlFor={props.id}
                className="mb-1.5 block text-sm font-semibold text-[#1B2430]"
            >
                {label}
            </label>
            <input
                {...props}
                className="block w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-[#1B2430] shadow-sm transition placeholder:text-slate-400 focus:border-[#1C9AD6] focus:outline-none focus:ring-2 focus:ring-[#1C9AD6]/30"
            />
            <InputError message={error} className="mt-1.5" />
        </div>
    );
}

/**
 * Bouton principal brandé (rouge Maroc-Modis).
 */
export function AuthButton({
    children,
    disabled,
    className = '',
    type = 'submit',
}: {
    children: React.ReactNode;
    disabled?: boolean;
    className?: string;
    type?: 'submit' | 'button';
}) {
    return (
        <button
            type={type}
            disabled={disabled}
            className={`inline-flex w-full items-center justify-center gap-2 rounded-xl bg-[#E23744] px-6 py-3 text-sm font-bold text-white shadow-lg shadow-[#E23744]/25 transition active:scale-95 hover:brightness-110 disabled:cursor-not-allowed disabled:opacity-60 ${className}`}
        >
            {children}
        </button>
    );
}
