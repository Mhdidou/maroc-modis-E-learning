import { BRAND } from '@/brand';

/**
 * Barre de progression brandée avec libellé en pourcentage.
 * Réutilisée par le tableau de bord apprenant et la page « Mes formations ».
 */
export default function ProgressBar({
    value,
    accent = BRAND.blue,
    showLabel = true,
    className = '',
}: {
    value: number;
    accent?: string;
    showLabel?: boolean;
    className?: string;
}) {
    const pct = Math.max(0, Math.min(100, Math.round(value)));
    const termine = pct >= 100;
    const couleur = termine ? '#16a34a' : accent;

    return (
        <div className={className}>
            <div className="flex items-center gap-3">
                <div
                    className="h-2.5 flex-1 overflow-hidden rounded-full bg-slate-100"
                    role="progressbar"
                    aria-valuenow={pct}
                    aria-valuemin={0}
                    aria-valuemax={100}
                >
                    <div
                        className="h-full rounded-full transition-all duration-500"
                        style={{ width: `${pct}%`, backgroundColor: couleur }}
                    />
                </div>
                {showLabel && (
                    <span
                        className="w-10 shrink-0 text-right text-sm font-bold tabular-nums"
                        style={{ color: couleur }}
                    >
                        {pct}%
                    </span>
                )}
            </div>
        </div>
    );
}
