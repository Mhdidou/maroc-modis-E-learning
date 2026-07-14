import { BRAND, LOGO_SRC } from '@/brand';
import { Link } from '@inertiajs/react';
import { LifeBuoy } from 'lucide-react';
import { PropsWithChildren, ReactNode } from 'react';

/**
 * Layout des pages d'authentification, aligné sur la charte de la page
 * d'accueil (FactoryIndex) : fond encre, logo Maroc-Modis, accents rouge/bleu.
 */
export default function Guest({
    title,
    subtitle,
    children,
}: PropsWithChildren<{ title?: ReactNode; subtitle?: ReactNode }>) {
    return (
        <div className="relative flex min-h-screen flex-col items-center justify-center overflow-hidden bg-[#1B2430] px-4 py-10 font-sans text-white antialiased">
            {/* Ambiance industrielle en dégradés (identique au hero de l'accueil) */}
            <div
                aria-hidden
                className="pointer-events-none absolute inset-0 opacity-70"
                style={{
                    background: `radial-gradient(60% 60% at 85% 10%, ${BRAND.blue}33 0%, transparent 60%), radial-gradient(50% 50% at 10% 90%, ${BRAND.red}33 0%, transparent 60%)`,
                }}
            />

            <div className="relative w-full max-w-md">
                {/* En-tête de marque */}
                <div className="mb-6 flex flex-col items-center text-center">
                    <Link
                        href="/"
                        className="flex items-center gap-3 rounded-xl bg-white/95 px-4 py-2.5 shadow-lg transition hover:bg-white"
                    >
                        <img
                            src={LOGO_SRC}
                            alt="Logo Maroc-Modis"
                            className="h-9 w-auto"
                        />
                        <span className="text-lg font-extrabold tracking-tight text-[#1B2430]">
                            E-learning
                        </span>
                    </Link>
                </div>

                {/* Carte du formulaire */}
                <div className="overflow-hidden rounded-2xl bg-white text-[#1B2430] shadow-2xl">
                    {/* Bandeau titre coloré */}
                    <div
                        className="px-7 py-5"
                        style={{
                            background: `linear-gradient(120deg, ${BRAND.ink} 0%, #24303f 100%)`,
                        }}
                    >
                        <h1 className="text-xl font-extrabold text-white">
                            {title ?? 'Connexion'}
                        </h1>
                        {subtitle && (
                            <p className="mt-1 text-sm text-slate-300">
                                {subtitle}
                            </p>
                        )}
                    </div>

                    <div className="px-7 py-6">{children}</div>
                </div>

                {/* Aide / support (repris du footer de l'accueil) */}
                <p className="mt-6 flex items-center justify-center gap-2 text-center text-xs text-slate-400">
                    <LifeBuoy className="h-4 w-4 text-[#1C9AD6]" />
                    Besoin d'aide ? Helpdesk interne ·{' '}
                    <a
                        href="mailto:helpdesk@triumph.com"
                        className="font-semibold text-slate-200 hover:text-white"
                    >
                        helpdesk@triumph.com
                    </a>
                </p>
            </div>
        </div>
    );
}
