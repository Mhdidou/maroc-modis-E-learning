import { CalendarCheck } from 'lucide-react';

export type JourActivite = {
    date: string;
    court: string;
    actif: boolean;
};

/**
 * « Activité durant la semaine » : bande des 7 jours (lundi → dimanche) avec
 * les jours de connexion mis en évidence, et le total de jours actifs.
 */
export default function WeekActivity({
    jours,
    actifs,
}: {
    jours: JourActivite[];
    actifs: number;
}) {
    return (
        <section className="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
            <div className="mb-4 flex items-center justify-between">
                <h2 className="flex items-center gap-2 text-lg font-bold">
                    <CalendarCheck className="h-5 w-5 text-[#1C9AD6]" />
                    Activité de la semaine
                </h2>
                <span className="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700">
                    {actifs} / 7 jours
                </span>
            </div>

            <div className="flex justify-between gap-2">
                {jours.map((j, index) => (
                    <div
                        key={j.date}
                        className="flex flex-1 flex-col items-center gap-2"
                    >
                        <div
                            className={`flex h-11 w-full items-center justify-center rounded-xl text-sm font-bold transition ${
                                j.actif
                                    ? 'bg-[#1C9AD6] text-white shadow-sm'
                                    : 'bg-slate-50 text-slate-400'
                            }`}
                            title={j.date}
                        >
                            {/* Rendu accessible : distingue les 3 jours en « M »/« J » via l'index. */}
                            {j.court}
                            <span className="sr-only">
                                {['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'][index]}
                            </span>
                        </div>
                        {j.actif && (
                            <span className="h-1.5 w-1.5 rounded-full bg-[#1C9AD6]" />
                        )}
                    </div>
                ))}
            </div>

            <p className="mt-4 text-sm text-slate-500">
                {actifs === 0
                    ? "Aucune connexion enregistrée cette semaine."
                    : actifs >= 5
                      ? 'Excellent rythme, continuez ainsi ! 🔥'
                      : 'Connectez-vous régulièrement pour garder le rythme.'}
            </p>
        </section>
    );
}
