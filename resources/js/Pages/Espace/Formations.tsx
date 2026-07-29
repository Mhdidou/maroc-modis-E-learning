import ObjectifDuJour, { ObjectifDuJourData } from '@/Components/ObjectifDuJour';
import ProgressBar from '@/Components/ProgressBar';
import WeekActivity, { JourActivite } from '@/Components/WeekActivity';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import { BadgeCheck, BookOpen, Layers, PlayCircle, Target } from 'lucide-react';

type Formation = {
    id: number;
    inscription_id: number;
    titre: string;
    type: string;
    statut: string;
    objectif_quotidien: number;
    modules_total: number;
    modules_faits: number;
    progression: number;
};

const STATUT_LABEL: Record<string, { label: string; classe: string }> = {
    non_commencee: { label: 'À commencer', classe: 'bg-slate-100 text-slate-600' },
    en_cours: { label: 'En cours', classe: 'bg-blue-100 text-blue-700' },
    terminee: { label: 'Terminée', classe: 'bg-green-100 text-green-700' },
};

export default function Formations({
    formations,
    objectifDuJour,
    activiteSemaine,
}: {
    formations: Formation[];
    objectifDuJour: ObjectifDuJourData;
    activiteSemaine: { jours: JourActivite[]; actifs: number };
}) {
    return (
        <AuthenticatedLayout
            header={
                <div>
                    <h1 className="flex items-center gap-2 text-2xl font-extrabold">
                        <BookOpen className="h-6 w-6 text-[#E23744]" />
                        Mes formations
                    </h1>
                    <p className="mt-1 text-sm text-slate-500">
                        Les formations qui vous ont été attribuées et votre
                        progression.
                    </p>
                </div>
            }
        >
            <Head title="Mes formations" />

            <div className="grid gap-6 lg:grid-cols-2">
                <ObjectifDuJour {...objectifDuJour} />
                <WeekActivity
                    jours={activiteSemaine.jours}
                    actifs={activiteSemaine.actifs}
                />
            </div>

            <section className="mt-6">
                {formations.length === 0 ? (
                    <div className="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
                        <p className="rounded-xl bg-slate-50 p-6 text-center text-sm text-slate-500">
                            Aucune formation attribuée pour le moment. Votre
                            superviseur vous inscrira prochainement.
                        </p>
                    </div>
                ) : (
                    <div className="grid gap-5 sm:grid-cols-2">
                        {formations.map((f) => {
                            const s =
                                STATUT_LABEL[f.statut] ??
                                STATUT_LABEL.non_commencee;
                            return (
                                <article
                                    key={f.inscription_id}
                                    className="flex flex-col rounded-2xl border border-slate-100 bg-white p-6 shadow-sm"
                                >
                                    <div className="mb-3 flex items-start justify-between gap-3">
                                        <h3 className="text-lg font-bold text-[#1B2430]">
                                            {f.titre}
                                        </h3>
                                        <span
                                            className={`shrink-0 rounded-full px-3 py-1 text-xs font-bold ${s.classe}`}
                                        >
                                            {s.label}
                                        </span>
                                    </div>

                                    <div className="mb-4 flex flex-wrap gap-4 text-sm text-slate-500">
                                        <span className="inline-flex items-center gap-1.5">
                                            <Layers className="h-4 w-4" />
                                            {f.modules_faits}/{f.modules_total}{' '}
                                            leçons
                                        </span>
                                        <span className="inline-flex items-center gap-1.5">
                                            <Target className="h-4 w-4" />
                                            {f.objectif_quotidien} / jour
                                        </span>
                                        {f.type === 'certifiante' && (
                                            <span className="inline-flex items-center gap-1.5 font-semibold text-green-600">
                                                <BadgeCheck className="h-4 w-4" />
                                                Certifiante
                                            </span>
                                        )}
                                    </div>

                                    <div className="mt-auto space-y-3">
                                        <ProgressBar value={f.progression} />
                                        <Link
                                            href={route(
                                                'apprentissage.formation',
                                                f.id,
                                            )}
                                            className="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-[#E23744] px-4 py-2.5 text-sm font-bold text-white transition hover:brightness-95"
                                        >
                                            <PlayCircle className="h-4 w-4" />
                                            {f.statut === 'terminee'
                                                ? 'Revoir la formation'
                                                : f.statut === 'en_cours'
                                                  ? 'Continuer'
                                                  : 'Commencer'}
                                        </Link>
                                    </div>
                                </article>
                            );
                        })}
                    </div>
                )}
            </section>
        </AuthenticatedLayout>
    );
}
