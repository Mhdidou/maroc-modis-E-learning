import { Link } from '@inertiajs/react';
import { ArrowLeft, Eye } from 'lucide-react';
import type { Role } from '@/types';

const ROLE_LABEL: Record<string, string> = {
    apprenant: 'Apprenant',
    formateur: 'Formateur',
    superviseur: 'Superviseur',
};

/**
 * Bandeau affiché lorsque l'administrateur consulte un espace en mode aperçu.
 * Rappelle que les données sont globales/représentatives et offre un retour
 * rapide vers l'espace Administration.
 */
export default function ApercuBanner({ role }: { role: Role | string }) {
    const label = ROLE_LABEL[role] ?? role;

    return (
        <div className="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm">
            <div className="flex items-center gap-3">
                <Eye className="h-5 w-5 shrink-0 text-amber-600" />
                <p className="text-amber-800">
                    <strong>Mode aperçu — vue {label}.</strong> Vous consultez cet
                    espace en tant qu'administrateur (données globales de la
                    plateforme, lecture seule).
                </p>
            </div>
            <Link
                href={route('dashboard')}
                className="inline-flex items-center gap-1.5 rounded-lg bg-amber-600 px-3 py-2 text-xs font-bold text-white transition hover:bg-amber-700"
            >
                <ArrowLeft className="h-4 w-4" />
                Revenir à l'administration
            </Link>
        </div>
    );
}
