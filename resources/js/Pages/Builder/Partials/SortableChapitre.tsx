import {
    DndContext,
    DragEndEvent,
    SensorDescriptor,
    closestCenter,
} from '@dnd-kit/core';
import {
    SortableContext,
    arrayMove,
    useSortable,
    verticalListSortingStrategy,
} from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { router, useForm } from '@inertiajs/react';
import { GripVertical, Plus, Trash2 } from 'lucide-react';
import { BuilderChapitre, BuilderModule, ModuleType } from '../types';
import SortableModule from './SortableModule';

const TYPES: { type: ModuleType; label: string }[] = [
    { type: 'video', label: 'Vidéo' },
    { type: 'pdf', label: 'PDF' },
    { type: 'quiz', label: 'Quiz' },
    { type: 'devoir', label: 'Devoir' },
];

/**
 * Chapitre déplaçable, contenant ses modules eux-mêmes déplaçables (DnD imbriqué).
 */
export default function SortableChapitre({
    chapitre,
    selection,
    onSelectModule,
    onReorderModules,
    onAddModule,
    sensors,
}: {
    chapitre: BuilderChapitre;
    selection: number | null;
    onSelectModule: (id: number) => void;
    onReorderModules: (modules: BuilderModule[]) => void;
    onAddModule: (type: ModuleType) => void;
    sensors: SensorDescriptor<object>[];
}) {
    const { attributes, listeners, setNodeRef, transform, transition } =
        useSortable({ id: chapitre.id });

    const titre = useForm({ titre: chapitre.titre });

    const reordonnerModules = (e: DragEndEvent) => {
        const { active, over } = e;
        if (!over || active.id === over.id) return;
        const ancien = chapitre.modules.findIndex((m) => m.id === active.id);
        const nouveau = chapitre.modules.findIndex((m) => m.id === over.id);
        const ordonnes = arrayMove(chapitre.modules, ancien, nouveau);
        onReorderModules(ordonnes);
        router.post(
            route('builder.modules.reordonner', chapitre.id),
            { ordre: ordonnes.map((m) => m.id) },
            { preserveScroll: true, preserveState: true },
        );
    };

    return (
        <div
            ref={setNodeRef}
            style={{ transform: CSS.Transform.toString(transform), transition }}
            className="rounded-2xl border border-slate-100 bg-white p-4 shadow-sm"
        >
            <div className="mb-3 flex items-center gap-2">
                <button
                    {...attributes}
                    {...listeners}
                    className="cursor-grab touch-none rounded p-1 text-slate-300 hover:text-slate-500 active:cursor-grabbing"
                    aria-label="Déplacer le module"
                >
                    <GripVertical className="h-5 w-5" />
                </button>
                <input
                    value={titre.data.titre}
                    onChange={(e) => titre.setData('titre', e.target.value)}
                    onBlur={() =>
                        titre.data.titre !== chapitre.titre &&
                        titre.put(route('builder.chapitres.update', chapitre.id), {
                            preserveScroll: true,
                        })
                    }
                    className="flex-1 rounded-lg border border-transparent bg-transparent px-1 text-base font-bold text-[#1B2430] hover:border-slate-200 focus:border-[#1C9AD6] focus:outline-none"
                />
                <button
                    onClick={() =>
                        confirm('Supprimer ce module et toutes ses leçons ?') &&
                        router.delete(
                            route('builder.chapitres.destroy', chapitre.id),
                            { preserveScroll: true },
                        )
                    }
                    className="rounded p-1.5 text-slate-400 hover:bg-red-50 hover:text-[#E23744]"
                    aria-label="Supprimer le module"
                >
                    <Trash2 className="h-4 w-4" />
                </button>
            </div>

            {/* Modules du chapitre */}
            <DndContext
                sensors={sensors}
                collisionDetection={closestCenter}
                onDragEnd={reordonnerModules}
            >
                <SortableContext
                    items={chapitre.modules.map((m) => m.id)}
                    strategy={verticalListSortingStrategy}
                >
                    <div className="space-y-2">
                        {chapitre.modules.map((m) => (
                            <SortableModule
                                key={m.id}
                                module={m}
                                actif={m.id === selection}
                                onSelect={() => onSelectModule(m.id)}
                            />
                        ))}
                    </div>
                </SortableContext>
            </DndContext>

            {chapitre.modules.length === 0 && (
                <p className="py-2 text-center text-xs text-slate-400">
                    Aucun module. Ajoutez-en un ci-dessous.
                </p>
            )}

            {/* Ajout de module par type */}
            <div className="mt-3 flex flex-wrap gap-2 border-t border-slate-100 pt-3">
                {TYPES.map((t) => (
                    <button
                        key={t.type}
                        onClick={() => onAddModule(t.type)}
                        className="inline-flex items-center gap-1 rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs font-semibold text-slate-600 hover:border-[#1C9AD6] hover:text-[#1C9AD6]"
                    >
                        <Plus className="h-3.5 w-3.5" />
                        {t.label}
                    </button>
                ))}
            </div>
        </div>
    );
}
