import ProgressBar from '@/Components/ProgressBar';
import { Target } from 'lucide-react';

export type ObjectifDuJourData = {
    objectif: number;
    faitAujourdhui: number;
    restantes: number;
};

/**
 * Encart « Objectif du jour » : combien de leçons compléter aujourd'hui,
 * combien sont déjà faites, combien il en reste.
 */
export default function ObjectifDuJour({
    objectif,
    faitAujourdhui,
    restantes,
}: ObjectifDuJourData) {
    const pct = objectif > 0 ? (faitAujourdhui / objectif) * 100 : 0;
    const atteint = objectif > 0 && faitAujourdhui >= objectif;

    return (
        <section className="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
            <div className="mb-4 flex items-center justify-between">
                <h2 className="flex items-center gap-2 text-lg font-bold">
                    <Target className="h-5 w-5 text-[#E23744]" />
                    Objectif du jour
                </h2>
                <span className="text-sm font-bold text-slate-500 tabular-nums">
                    {faitAujourdhui} / {objectif || 0} leçons
                </span>
            </div>

            {objectif === 0 ? (
                <p className="rounded-xl bg-slate-50 p-4 text-center text-sm text-slate-500">
                    Aucune formation active. Votre superviseur vous en affectera
                    prochainement.
                </p>
            ) : (
                <>
                    <ProgressBar value={pct} accent="#E23744" />
                    <p className="mt-3 text-sm text-slate-600">
                        {atteint ? (
                            <span className="font-semibold text-green-600">
                                🎉 Objectif atteint pour aujourd'hui, bravo !
                            </span>
                        ) : (
                            <>
                                Plus que{' '}
                                <strong className="text-[#1B2430]">
                                    {restantes} leçon{restantes > 1 ? 's' : ''}
                                </strong>{' '}
                                à compléter aujourd'hui.
                            </>
                        )}
                    </p>
                </>
            )}
        </section>
    );
}
