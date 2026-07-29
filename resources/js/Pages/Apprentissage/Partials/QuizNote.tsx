import {
    DemarrageQuiz,
    ResultatQuiz,
    demarrerQuiz,
    soumettreQuiz,
} from '@/lib/apprentissage';
import { useMutation } from '@tanstack/react-query';
import { AlertTriangle, Clock, ListChecks, RotateCcw } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { ModuleLecture } from '../types';

const mmss = (s: number) =>
    `${String(Math.floor(s / 60)).padStart(2, '0')}:${String(Math.max(0, s) % 60).padStart(2, '0')}`;

export default function QuizNote({
    module,
    onDone,
}: {
    module: ModuleLecture;
    onDone: () => void;
}) {
    const [phase, setPhase] = useState<'intro' | 'encours' | 'resultat'>('intro');
    const [session, setSession] = useState<DemarrageQuiz | null>(null);
    const [reponses, setReponses] = useState<Record<number, string>>({});
    const [confirme, setConfirme] = useState(false);
    const [restant, setRestant] = useState(0);
    const [resultat, setResultat] = useState<ResultatQuiz | null>(null);
    const soumisRef = useRef(false);

    const demarrage = useMutation({
        mutationFn: () => demarrerQuiz(module.id),
        onSuccess: (data) => {
            setSession(data);
            setReponses({});
            setConfirme(false);
            setResultat(null);
            soumisRef.current = false;
            setRestant((data.data.duree_minutes ?? 10) * 60);
            setPhase('encours');
        },
    });

    const soumission = useMutation({
        mutationFn: () =>
            soumettreQuiz(session!.data.id, reponses, true),
        onSuccess: (data) => {
            setResultat(data);
            setPhase('resultat');
        },
    });

    // Timer serveur-borné : à 0, soumission automatique (une seule fois).
    useEffect(() => {
        if (phase !== 'encours') return;
        if (restant <= 0) {
            if (!soumisRef.current) {
                soumisRef.current = true;
                soumission.mutate();
            }
            return;
        }
        const id = setTimeout(() => setRestant((s) => s - 1), 1000);
        return () => clearTimeout(id);
    }, [phase, restant]); // eslint-disable-line react-hooks/exhaustive-deps

    const envoyer = () => {
        if (soumisRef.current) return;
        soumisRef.current = true;
        soumission.mutate();
    };

    /* ---- Intro ---- */
    if (phase === 'intro') {
        const dejaReussi = module.reussi;
        return (
            <div className="space-y-4 rounded-2xl border border-slate-100 bg-white p-6">
                <h3 className="flex items-center gap-2 text-lg font-bold">
                    <ListChecks className="h-5 w-5 text-[#7C3AED]" />
                    {module.titre}
                </h3>
                <ul className="space-y-1 text-sm text-slate-600">
                    <li>Seuil de réussite : {module.seuil_reussite}%</li>
                    <li>
                        {module.nb_questions_tirees} questions tirées ·{' '}
                        {module.duree_minutes} min
                    </li>
                    <li>
                        Tentatives : {module.tentatives_utilisees}/
                        {module.tentatives_max} (au 3e échec, le module est
                        réinitialisé)
                    </li>
                </ul>

                {module.termine || dejaReussi ? (
                    <div className="rounded-xl bg-green-50 px-4 py-2.5 text-sm font-bold text-green-700">
                        Quiz réussi {module.score !== null && `— ${module.score}%`}
                    </div>
                ) : (
                    <button
                        disabled={demarrage.isPending}
                        onClick={() => demarrage.mutate()}
                        className="inline-flex items-center gap-2 rounded-xl bg-[#7C3AED] px-5 py-2.5 text-sm font-bold text-white disabled:opacity-60"
                    >
                        Commencer le quiz
                    </button>
                )}
            </div>
        );
    }

    /* ---- En cours ---- */
    if (phase === 'encours' && session) {
        const complet = session.questions.every((q) => reponses[q.id]);
        return (
            <div className="space-y-4 rounded-2xl border border-slate-100 bg-white p-6">
                <div className="flex items-center justify-between">
                    <h3 className="text-lg font-bold">{module.titre}</h3>
                    <span
                        className={`inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-sm font-bold ${
                            restant <= 30
                                ? 'bg-red-100 text-[#E23744]'
                                : 'bg-slate-100 text-slate-700'
                        }`}
                    >
                        <Clock className="h-4 w-4" />
                        {mmss(restant)}
                    </span>
                </div>

                {session.questions.map((q, i) => (
                    <div key={q.id} className="rounded-xl border border-slate-100 p-4">
                        <p className="mb-3 font-semibold text-[#1B2430]">
                            {i + 1}. {q.enonce}
                        </p>
                        <div className="space-y-2">
                            {q.options.map((opt) => (
                                <label
                                    key={opt}
                                    className={`flex cursor-pointer items-center gap-2 rounded-lg border px-3 py-2 text-sm ${
                                        reponses[q.id] === opt
                                            ? 'border-[#7C3AED] bg-[#7C3AED]/5'
                                            : 'border-slate-200'
                                    }`}
                                >
                                    <input
                                        type="radio"
                                        name={`q-${q.id}`}
                                        checked={reponses[q.id] === opt}
                                        onChange={() =>
                                            setReponses((r) => ({
                                                ...r,
                                                [q.id]: opt,
                                            }))
                                        }
                                    />
                                    {opt}
                                </label>
                            ))}
                        </div>
                    </div>
                ))}

                <label className="flex items-center gap-2 text-sm font-semibold text-[#1B2430]">
                    <input
                        type="checkbox"
                        checked={confirme}
                        onChange={(e) => setConfirme(e.target.checked)}
                    />
                    Je confirme vouloir envoyer mes réponses définitivement.
                </label>

                <button
                    disabled={!confirme || !complet || soumission.isPending}
                    onClick={envoyer}
                    className="inline-flex items-center gap-2 rounded-xl bg-[#E23744] px-5 py-2.5 text-sm font-bold text-white disabled:opacity-50"
                >
                    Envoyer mes réponses
                </button>
            </div>
        );
    }

    /* ---- Résultat ---- */
    if (phase === 'resultat' && resultat) {
        return (
            <div className="space-y-4 rounded-2xl border border-slate-100 bg-white p-6">
                <div
                    className={`rounded-xl p-4 text-center ${
                        resultat.reussi
                            ? 'bg-green-50 text-green-700'
                            : 'bg-red-50 text-[#E23744]'
                    }`}
                >
                    <p className="text-3xl font-extrabold">{resultat.score}%</p>
                    <p className="text-sm font-bold">
                        {resultat.reussi
                            ? 'Réussi !'
                            : `Échec (seuil ${resultat.seuil}%)`}
                    </p>
                </div>

                {resultat.reset_chapitre && (
                    <div className="flex items-start gap-2 rounded-xl bg-amber-50 p-3 text-sm text-amber-800">
                        <AlertTriangle className="mt-0.5 h-4 w-4 shrink-0" />
                        3e échec : tout le module a été réinitialisé. Vous devez
                        le reprendre depuis le début.
                    </div>
                )}

                {resultat.reussi ? (
                    <button
                        onClick={onDone}
                        className="rounded-xl bg-[#1B2430] px-5 py-2.5 text-sm font-bold text-white"
                    >
                        Continuer
                    </button>
                ) : resultat.reset_chapitre ? (
                    <button
                        onClick={onDone}
                        className="rounded-xl bg-[#1B2430] px-5 py-2.5 text-sm font-bold text-white"
                    >
                        Revenir au module
                    </button>
                ) : (
                    <button
                        onClick={() => demarrage.mutate()}
                        disabled={demarrage.isPending}
                        className="inline-flex items-center gap-2 rounded-xl bg-[#7C3AED] px-5 py-2.5 text-sm font-bold text-white disabled:opacity-60"
                    >
                        <RotateCcw className="h-4 w-4" />
                        Réessayer ({resultat.tentatives_restantes} restante
                        {resultat.tentatives_restantes > 1 ? 's' : ''})
                    </button>
                )}
            </div>
        );
    }

    return null;
}
