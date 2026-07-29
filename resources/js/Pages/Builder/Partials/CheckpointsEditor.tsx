import { router, useForm } from '@inertiajs/react';
import { Check, Clock, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { BuilderModule, Checkpoint } from '../types';
import ReponsesFausses from './ReponsesFausses';

const champ =
    'block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-[#1C9AD6] focus:outline-none focus:ring-2 focus:ring-[#1C9AD6]/30';

const mmss = (s: number) =>
    `${String(Math.floor(s / 60)).padStart(2, '0')}:${String(s % 60).padStart(2, '0')}`;

/**
 * Quiz-surprise vidéo : questions bloquantes placées à un timestamp précis,
 * chacune avec l'explication de la bonne réponse.
 */
export default function CheckpointsEditor({ module }: { module: BuilderModule }) {
    return (
        <div className="space-y-3">
            <h4 className="flex items-center gap-2 text-sm font-bold">
                <Clock className="h-4 w-4 text-[#1C9AD6]" />
                Points de contrôle ({module.checkpoints.length})
            </h4>

            {module.checkpoints.map((cp) => (
                <CheckpointCard key={cp.id} checkpoint={cp} />
            ))}

            <CheckpointAdd moduleId={module.id} />
        </div>
    );
}

function ChampsCheckpoint({
    data,
    setData,
    errors,
}: {
    data: {
        position_secondes: number;
        enonce: string;
        bonne_reponse: string;
        mauvaises_reponses: string[];
        explication: string;
    };
    setData: (k: string, v: unknown) => void;
    errors: Record<string, string>;
}) {
    return (
        <>
            <div>
                <label className="mb-1.5 block text-sm font-semibold">
                    Timestamp (secondes) — {mmss(data.position_secondes || 0)}
                </label>
                <input
                    type="number"
                    min={0}
                    value={data.position_secondes}
                    onChange={(e) =>
                        setData('position_secondes', Number(e.target.value))
                    }
                    className={champ}
                />
                {errors.position_secondes && (
                    <p className="mt-1 text-xs text-[#E23744]">
                        {errors.position_secondes}
                    </p>
                )}
            </div>
            <textarea
                value={data.enonce}
                onChange={(e) => setData('enonce', e.target.value)}
                className={champ}
                rows={2}
                placeholder="Question posée à ce moment"
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
            <div>
                <label className="mb-1.5 block text-sm font-semibold">
                    Explication (affichée après réponse)
                </label>
                <textarea
                    value={data.explication}
                    onChange={(e) => setData('explication', e.target.value)}
                    className={champ}
                    rows={2}
                    placeholder="Pourquoi cette réponse est correcte"
                />
                {errors.explication && (
                    <p className="mt-1 text-xs text-[#E23744]">
                        {errors.explication}
                    </p>
                )}
            </div>
        </>
    );
}

function CheckpointCard({ checkpoint }: { checkpoint: Checkpoint }) {
    const [ouvert, setOuvert] = useState(false);
    const form = useForm({
        position_secondes: checkpoint.position_secondes,
        enonce: checkpoint.enonce,
        bonne_reponse: checkpoint.bonne_reponse,
        mauvaises_reponses: checkpoint.mauvaises_reponses,
        explication: checkpoint.explication,
    });

    if (!ouvert) {
        return (
            <div className="flex items-center justify-between rounded-lg border border-slate-100 bg-slate-50 px-3 py-2">
                <span className="flex items-center gap-2 truncate text-sm text-[#1B2430]">
                    <span className="rounded bg-[#1C9AD6]/10 px-1.5 py-0.5 font-mono text-xs text-[#1C9AD6]">
                        {mmss(checkpoint.position_secondes)}
                    </span>
                    <span className="truncate">{checkpoint.enonce}</span>
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
                            confirm('Supprimer ce point de contrôle ?') &&
                            router.delete(
                                route(
                                    'builder.checkpoints.destroy',
                                    checkpoint.id,
                                ),
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
        <div className="space-y-3 rounded-lg border border-[#1C9AD6]/30 bg-white p-3">
            <ChampsCheckpoint
                data={form.data}
                setData={form.setData as (k: string, v: unknown) => void}
                errors={form.errors as Record<string, string>}
            />
            <div className="flex justify-end gap-2">
                <button
                    onClick={() => setOuvert(false)}
                    className="rounded-lg px-3 py-1.5 text-xs font-semibold text-slate-500 hover:bg-slate-100"
                >
                    Annuler
                </button>
                <button
                    disabled={form.processing}
                    onClick={() =>
                        form.put(
                            route('builder.checkpoints.update', checkpoint.id),
                            {
                                preserveScroll: true,
                                onSuccess: () => setOuvert(false),
                            },
                        )
                    }
                    className="inline-flex items-center gap-1 rounded-lg bg-[#1C9AD6] px-3 py-1.5 text-xs font-bold text-white disabled:opacity-60"
                >
                    <Check className="h-3.5 w-3.5" />
                    Enregistrer
                </button>
            </div>
        </div>
    );
}

function CheckpointAdd({ moduleId }: { moduleId: number }) {
    const form = useForm({
        position_secondes: 30,
        enonce: '',
        bonne_reponse: '',
        mauvaises_reponses: [''],
        explication: '',
    });

    return (
        <div className="space-y-3 rounded-lg border border-dashed border-slate-300 bg-white p-3">
            <ChampsCheckpoint
                data={form.data}
                setData={form.setData as (k: string, v: unknown) => void}
                errors={form.errors as Record<string, string>}
            />
            <button
                disabled={form.processing}
                onClick={() =>
                    form.post(route('builder.checkpoints.store', moduleId), {
                        preserveScroll: true,
                        onSuccess: () => form.reset(),
                    })
                }
                className="inline-flex items-center gap-1 rounded-lg bg-[#1C9AD6] px-3 py-1.5 text-xs font-bold text-white disabled:opacity-60"
            >
                <Plus className="h-3.5 w-3.5" />
                Ajouter le point de contrôle
            </button>
        </div>
    );
}
