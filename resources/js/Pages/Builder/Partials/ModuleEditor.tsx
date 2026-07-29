import { router, useForm } from '@inertiajs/react';
import { FileText, Save, Trash2 } from 'lucide-react';
import { BuilderModule, TYPE_MODULE_META } from '../types';
import CheckpointsEditor from './CheckpointsEditor';
import QuestionsEditor from './QuestionsEditor';
import TeleverserFichier from './TeleverserFichier';

const champ =
    'block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-[#1C9AD6] focus:outline-none focus:ring-2 focus:ring-[#1C9AD6]/30';

/**
 * Panneau d'édition d'un module. Les métadonnées (titre, contenu, consignes,
 * config quiz) sont enregistrées en un PUT ; checkpoints et questions ont leurs
 * propres endpoints. Ce composant est remonté (key=module.id) à chaque
 * changement de sélection pour repartir d'un état propre.
 */
export default function ModuleEditor({ module }: { module: BuilderModule }) {
    const meta = TYPE_MODULE_META[module.type];
    const { data, setData, put, processing, errors } = useForm({
        type: module.type,
        titre: module.titre,
        consignes: module.consignes ?? '',
        nb_questions_tirees: module.nb_questions_tirees ?? 5,
        seuil_reussite: module.seuil_reussite ?? 70,
        duree_minutes: module.duree_minutes ?? 10,
    });

    return (
        <div className="space-y-5">
            <div className="flex items-center justify-between">
                <span
                    className="rounded-full px-3 py-1 text-xs font-bold text-white"
                    style={{ backgroundColor: meta.couleur }}
                >
                    {meta.label}
                </span>
                <button
                    onClick={() =>
                        confirm('Supprimer cette leçon ?') &&
                        router.delete(route('builder.modules.destroy', module.id), {
                            preserveScroll: true,
                        })
                    }
                    className="inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-xs font-semibold text-[#E23744] hover:bg-red-50"
                >
                    <Trash2 className="h-4 w-4" />
                    Supprimer
                </button>
            </div>

            {/* Métadonnées communes / spécifiques */}
            <div className="space-y-4 rounded-xl border border-slate-100 bg-white p-4">
                <div>
                    <label className="mb-1.5 block text-sm font-semibold">
                        Titre de la leçon
                    </label>
                    <input
                        type="text"
                        value={data.titre}
                        onChange={(e) => setData('titre', e.target.value)}
                        className={champ}
                    />
                    {errors.titre && (
                        <p className="mt-1 text-xs text-[#E23744]">
                            {errors.titre}
                        </p>
                    )}
                </div>

                {module.type === 'devoir' && (
                    <div>
                        <label className="mb-1.5 block text-sm font-semibold">
                            Consignes du devoir
                        </label>
                        <textarea
                            value={data.consignes}
                            onChange={(e) => setData('consignes', e.target.value)}
                            className={champ}
                            rows={4}
                            placeholder="Ce que l'apprenant doit rendre…"
                        />
                    </div>
                )}

                {module.type === 'quiz' && (
                    <div className="grid grid-cols-3 gap-3">
                        <div>
                            <label className="mb-1.5 block text-xs font-semibold">
                                Questions tirées
                            </label>
                            <input
                                type="number"
                                min={1}
                                value={data.nb_questions_tirees}
                                onChange={(e) =>
                                    setData(
                                        'nb_questions_tirees',
                                        Number(e.target.value),
                                    )
                                }
                                className={champ}
                            />
                        </div>
                        <div>
                            <label className="mb-1.5 block text-xs font-semibold">
                                Seuil (%)
                            </label>
                            <input
                                type="number"
                                min={1}
                                max={100}
                                value={data.seuil_reussite}
                                onChange={(e) =>
                                    setData(
                                        'seuil_reussite',
                                        Number(e.target.value),
                                    )
                                }
                                className={champ}
                            />
                        </div>
                        <div>
                            <label className="mb-1.5 block text-xs font-semibold">
                                Durée (min)
                            </label>
                            <input
                                type="number"
                                min={1}
                                value={data.duree_minutes}
                                onChange={(e) =>
                                    setData('duree_minutes', Number(e.target.value))
                                }
                                className={champ}
                            />
                        </div>
                    </div>
                )}

                <button
                    disabled={processing}
                    onClick={() =>
                        put(route('builder.modules.update', module.id), {
                            preserveScroll: true,
                        })
                    }
                    className="inline-flex items-center gap-1.5 rounded-lg bg-[#1B2430] px-3.5 py-2 text-sm font-bold text-white disabled:opacity-60"
                >
                    <Save className="h-4 w-4" />
                    Enregistrer la leçon
                </button>
            </div>

            {/* Import du fichier : support principal (pdf/vidéo) ou pièce jointe
                d'explication facultative pour un devoir. */}
            {(module.type === 'pdf' ||
                module.type === 'video' ||
                module.type === 'devoir') && (
                <div className="rounded-xl border border-slate-100 bg-white p-4">
                    <TeleverserFichier module={module} />
                </div>
            )}

            {/* Éditeurs spécifiques */}
            {module.type === 'video' && (
                <div className="rounded-xl border border-slate-100 bg-white p-4">
                    <CheckpointsEditor module={module} />
                </div>
            )}
            {module.type === 'quiz' && (
                <div className="rounded-xl border border-slate-100 bg-white p-4">
                    <QuestionsEditor module={module} />
                </div>
            )}
            {module.type === 'pdf' && (
                <p className="flex items-center gap-2 rounded-xl bg-slate-50 p-4 text-xs text-slate-500">
                    <FileText className="h-4 w-4" />
                    La leçon PDF est validée par l'apprenant après consultation.
                </p>
            )}
        </div>
    );
}
