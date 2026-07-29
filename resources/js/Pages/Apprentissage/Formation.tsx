import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import {
    ArrowLeft,
    CheckCircle2,
    ClipboardCheck,
    FileText,
    ListChecks,
    Lock,
    Video,
} from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import DevoirModule from './Partials/DevoirModule';
import PdfModule from './Partials/PdfModule';
import QuizNote from './Partials/QuizNote';
import VideoPlayer from './Partials/VideoPlayer';
import { FormationLecture, ModuleLecture, ModuleType } from './types';

const ICONE: Record<ModuleType, typeof FileText> = {
    pdf: FileText,
    video: Video,
    quiz: ListChecks,
    devoir: ClipboardCheck,
};

export default function Formation({
    formation,
}: {
    formation: FormationLecture;
}) {
    const modules = useMemo(
        () => formation.chapitres.flatMap((c) => c.modules),
        [formation],
    );

    const [selection, setSelection] = useState<number | null>(null);

    // Sélection par défaut : premier module accessible non terminé.
    useEffect(() => {
        const existe = modules.some((m) => m.id === selection);
        if (!existe) {
            const cible =
                modules.find((m) => m.accessible && !m.termine) ??
                modules.find((m) => m.accessible) ??
                null;
            setSelection(cible?.id ?? null);
        }
    }, [modules, selection]);

    const moduleSelectionne = modules.find((m) => m.id === selection);
    const rafraichir = () => router.reload({ only: ['formation'] });

    return (
        <AuthenticatedLayout
            header={
                <div>
                    <Link
                        href={route('mes-formations.index')}
                        className="mb-2 inline-flex items-center gap-1 text-sm font-semibold text-[#1C9AD6] hover:underline"
                    >
                        <ArrowLeft className="h-4 w-4" />
                        Mes formations
                    </Link>
                    <h1 className="text-2xl font-extrabold">{formation.titre}</h1>
                    <p className="mt-1 text-sm text-slate-500">
                        {formation.type === 'certifiante'
                            ? 'Formation certifiante'
                            : 'Formation'}{' '}
                        · progression enregistrée automatiquement.
                    </p>
                </div>
            }
        >
            <Head title={formation.titre} />

            <div className="grid gap-6 lg:grid-cols-[320px_1fr]">
                {/* Sommaire */}
                <aside className="space-y-4">
                    {formation.chapitres.map((chapitre) => (
                        <div
                            key={chapitre.id}
                            className="rounded-2xl border border-slate-100 bg-white p-4 shadow-sm"
                        >
                            <h3 className="mb-3 text-sm font-bold text-[#1B2430]">
                                {chapitre.titre}
                            </h3>
                            <ul className="space-y-1">
                                {chapitre.modules.map((m) => (
                                    <li key={m.id}>
                                        <ModuleRow
                                            module={m}
                                            actif={m.id === selection}
                                            onSelect={() =>
                                                m.accessible &&
                                                setSelection(m.id)
                                            }
                                        />
                                    </li>
                                ))}
                            </ul>
                        </div>
                    ))}
                </aside>

                {/* Lecteur du module sélectionné */}
                <section>
                    {moduleSelectionne ? (
                        <Viewer
                            key={moduleSelectionne.id}
                            module={moduleSelectionne}
                            onDone={rafraichir}
                        />
                    ) : (
                        <div className="rounded-2xl border border-dashed border-slate-200 bg-white p-10 text-center text-sm text-slate-500">
                            Aucun module disponible.
                        </div>
                    )}
                </section>
            </div>
        </AuthenticatedLayout>
    );
}

function ModuleRow({
    module,
    actif,
    onSelect,
}: {
    module: ModuleLecture;
    actif: boolean;
    onSelect: () => void;
}) {
    const Icone = module.termine
        ? CheckCircle2
        : !module.accessible
          ? Lock
          : ICONE[module.type];

    const couleur = module.termine
        ? 'text-green-600'
        : !module.accessible
          ? 'text-slate-300'
          : 'text-[#1C9AD6]';

    return (
        <button
            onClick={onSelect}
            disabled={!module.accessible}
            className={`flex w-full items-center gap-2 rounded-lg px-2.5 py-2 text-left text-sm transition ${
                actif
                    ? 'bg-[#1C9AD6]/10 font-semibold text-[#1B2430]'
                    : module.accessible
                      ? 'text-[#1B2430] hover:bg-slate-50'
                      : 'cursor-not-allowed text-slate-400'
            }`}
        >
            <Icone className={`h-4 w-4 shrink-0 ${couleur}`} />
            <span className="truncate">{module.titre}</span>
        </button>
    );
}

function Viewer({
    module,
    onDone,
}: {
    module: ModuleLecture;
    onDone: () => void;
}) {
    switch (module.type) {
        case 'video':
            return <VideoPlayer module={module} onDone={onDone} />;
        case 'quiz':
            return <QuizNote module={module} onDone={onDone} />;
        case 'devoir':
            return <DevoirModule module={module} onDone={onDone} />;
        default:
            return <PdfModule module={module} onDone={onDone} />;
    }
}
