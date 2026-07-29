import { router, useForm } from '@inertiajs/react';
import { Check, ListChecks, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { BuilderModule, Question } from '../types';
import ReponsesFausses from './ReponsesFausses';

const champ =
    'block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-[#1C9AD6] focus:outline-none focus:ring-2 focus:ring-[#1C9AD6]/30';

/**
 * Banque de questions d'un quiz noté. Tirage aléatoire côté serveur : plus il y
 * a de questions, plus le quiz résiste à la mémorisation.
 */
export default function QuestionsEditor({ module }: { module: BuilderModule }) {
    return (
        <div className="space-y-3">
            <div className="flex items-center justify-between">
                <h4 className="flex items-center gap-2 text-sm font-bold">
                    <ListChecks className="h-4 w-4 text-[#7C3AED]" />
                    Banque de questions ({module.questions.length})
                </h4>
                <span className="text-xs text-slate-400">
                    {module.nb_questions_tirees ?? '?'} tirée(s) par tentative
                </span>
            </div>

            {module.questions.map((q) => (
                <QuestionCard key={q.id} question={q} />
            ))}

            <QuestionAdd moduleId={module.id} />
        </div>
    );
}

function QuestionCard({ question }: { question: Question }) {
    const [ouvert, setOuvert] = useState(false);
    const { data, setData, put, processing, errors } = useForm({
        enonce: question.enonce,
        bonne_reponse: question.bonne_reponse,
        mauvaises_reponses: question.mauvaises_reponses,
    });

    if (!ouvert) {
        return (
            <div className="flex items-center justify-between rounded-lg border border-slate-100 bg-slate-50 px-3 py-2">
                <span className="truncate text-sm text-[#1B2430]">
                    {question.enonce}
                </span>
                <div className="flex shrink-0 items-center gap-1">
                    <button
                        onClick={() => setOuvert(true)}
                        className="rounded px-2 py-1 text-xs font-semibold text-[#1C9AD6] hover:underline"
                    >
                        Modifier
                    </button>
                    <button
                        onClick={() =>
                            confirm('Supprimer cette question ?') &&
                            router.delete(
                                route('builder.questions.destroy', question.id),
                                { preserveScroll: true },
                            )
                        }
                        className="rounded p-1 text-slate-400 hover:bg-red-50 hover:text-[#E23744]"
                        aria-label="Supprimer"
                    >
                        <Trash2 className="h-4 w-4" />
                    </button>
                </div>
            </div>
        );
    }

    return (
        <div className="space-y-3 rounded-lg border border-[#7C3AED]/30 bg-white p-3">
            <textarea
                value={data.enonce}
                onChange={(e) => setData('enonce', e.target.value)}
                className={champ}
                rows={2}
                placeholder="Énoncé"
            />
            {errors.enonce && (
                <p className="text-xs text-[#E23744]">{errors.enonce}</p>
            )}
            <input
                type="text"
                value={data.bonne_reponse}
                onChange={(e) => setData('bonne_reponse', e.target.value)}
                className={champ}
                placeholder="Bonne réponse"
            />
            {errors.bonne_reponse && (
                <p className="text-xs text-[#E23744]">{errors.bonne_reponse}</p>
            )}
            <ReponsesFausses
                valeurs={data.mauvaises_reponses}
                onChange={(v) => setData('mauvaises_reponses', v)}
                erreur={errors.mauvaises_reponses}
            />
            <div className="flex justify-end gap-2">
                <button
                    onClick={() => setOuvert(false)}
                    className="rounded-lg px-3 py-1.5 text-xs font-semibold text-slate-500 hover:bg-slate-100"
                >
                    Annuler
                </button>
                <button
                    disabled={processing}
                    onClick={() =>
                        put(route('builder.questions.update', question.id), {
                            preserveScroll: true,
                            onSuccess: () => setOuvert(false),
                        })
                    }
                    className="inline-flex items-center gap-1 rounded-lg bg-[#7C3AED] px-3 py-1.5 text-xs font-bold text-white disabled:opacity-60"
                >
                    <Check className="h-3.5 w-3.5" />
                    Enregistrer
                </button>
            </div>
        </div>
    );
}

function QuestionAdd({ moduleId }: { moduleId: number }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        enonce: '',
        bonne_reponse: '',
        mauvaises_reponses: [''],
    });

    return (
        <div className="space-y-3 rounded-lg border border-dashed border-slate-300 bg-white p-3">
            <textarea
                value={data.enonce}
                onChange={(e) => setData('enonce', e.target.value)}
                className={champ}
                rows={2}
                placeholder="Nouvel énoncé…"
            />
            {errors.enonce && (
                <p className="text-xs text-[#E23744]">{errors.enonce}</p>
            )}
            <input
                type="text"
                value={data.bonne_reponse}
                onChange={(e) => setData('bonne_reponse', e.target.value)}
                className={champ}
                placeholder="Bonne réponse"
            />
            {errors.bonne_reponse && (
                <p className="text-xs text-[#E23744]">{errors.bonne_reponse}</p>
            )}
            <ReponsesFausses
                valeurs={data.mauvaises_reponses}
                onChange={(v) => setData('mauvaises_reponses', v)}
                erreur={errors.mauvaises_reponses}
            />
            <button
                disabled={processing}
                onClick={() =>
                    post(route('builder.questions.store', moduleId), {
                        preserveScroll: true,
                        onSuccess: () => reset(),
                    })
                }
                className="inline-flex items-center gap-1 rounded-lg bg-[#7C3AED] px-3 py-1.5 text-xs font-bold text-white disabled:opacity-60"
            >
                <Plus className="h-3.5 w-3.5" />
                Ajouter la question
            </button>
        </div>
    );
}
