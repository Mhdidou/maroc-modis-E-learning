import { useSortable } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import {
    ClipboardCheck,
    FileText,
    GripVertical,
    ListChecks,
    Video,
} from 'lucide-react';
import { BuilderModule, ModuleType, TYPE_MODULE_META } from '../types';

const ICONE: Record<ModuleType, typeof FileText> = {
    pdf: FileText,
    video: Video,
    quiz: ListChecks,
    devoir: ClipboardCheck,
};

/**
 * Ligne de module déplaçable et sélectionnable (ouvre l'éditeur à droite).
 */
export default function SortableModule({
    module,
    actif,
    onSelect,
}: {
    module: BuilderModule;
    actif: boolean;
    onSelect: () => void;
}) {
    const { attributes, listeners, setNodeRef, transform, transition } =
        useSortable({ id: module.id });
    const Icone = ICONE[module.type];
    const meta = TYPE_MODULE_META[module.type];

    return (
        <div
            ref={setNodeRef}
            style={{ transform: CSS.Transform.toString(transform), transition }}
            className={`flex items-center gap-2 rounded-lg border px-2 py-2 transition ${
                actif
                    ? 'border-[#1C9AD6] bg-[#1C9AD6]/5'
                    : 'border-slate-100 bg-slate-50 hover:border-slate-200'
            }`}
        >
            <button
                {...attributes}
                {...listeners}
                className="cursor-grab touch-none rounded p-0.5 text-slate-300 hover:text-slate-500 active:cursor-grabbing"
                aria-label="Déplacer la leçon"
            >
                <GripVertical className="h-4 w-4" />
            </button>
            <button
                onClick={onSelect}
                className="flex min-w-0 flex-1 items-center gap-2 text-left"
            >
                <Icone
                    className="h-4 w-4 shrink-0"
                    style={{ color: meta.couleur }}
                />
                <span className="truncate text-sm font-semibold text-[#1B2430]">
                    {module.titre}
                </span>
            </button>
        </div>
    );
}
