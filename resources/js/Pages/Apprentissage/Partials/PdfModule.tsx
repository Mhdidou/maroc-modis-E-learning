import { terminerModule } from '@/lib/apprentissage';
import { useMutation } from '@tanstack/react-query';
import { CheckCircle2, Download, FileText } from 'lucide-react';
import { ModuleLecture } from '../types';

/**
 * Module PDF : pas d'aperçu intégré — un bouton de téléchargement du document
 * (façon Dropbox/Mega), puis complétion explicite (enregistrée côté serveur).
 */
export default function PdfModule({
    module,
    onDone,
}: {
    module: ModuleLecture;
    onDone: () => void;
}) {
    const completion = useMutation({
        mutationFn: () => terminerModule(module.id),
        onSuccess: () => onDone(),
    });

    const nomFichier = module.contenu?.split('/').pop() ?? 'document.pdf';

    return (
        <div className="space-y-4">
            <div className="rounded-2xl border border-slate-100 bg-white p-6">
                <div className="flex items-center gap-4">
                    <div className="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-[#E23744]/10">
                        <FileText className="h-7 w-7 text-[#E23744]" />
                    </div>
                    <div className="min-w-0 flex-1">
                        <p className="truncate font-bold text-[#1B2430]">
                            {module.titre}
                        </p>
                        <p className="truncate text-xs text-slate-500">
                            {nomFichier} · PDF
                        </p>
                    </div>
                </div>

                {module.contenu ? (
                    <a
                        href={module.contenu}
                        download
                        target="_blank"
                        rel="noreferrer"
                        className="mt-5 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-[#1C9AD6] px-5 py-3 text-sm font-bold text-white transition hover:brightness-95"
                    >
                        <Download className="h-5 w-5" />
                        Télécharger le document
                    </a>
                ) : (
                    <p className="mt-5 rounded-xl bg-slate-50 p-4 text-center text-sm text-slate-400">
                        Document non disponible.
                    </p>
                )}
            </div>

            {module.termine ? (
                <div className="inline-flex items-center gap-2 rounded-xl bg-green-50 px-4 py-2.5 text-sm font-bold text-green-700">
                    <CheckCircle2 className="h-5 w-5" />
                    Leçon terminée
                </div>
            ) : (
                <button
                    disabled={completion.isPending}
                    onClick={() => completion.mutate()}
                    className="inline-flex items-center gap-2 rounded-xl bg-[#1B2430] px-5 py-2.5 text-sm font-bold text-white disabled:opacity-60"
                >
                    <CheckCircle2 className="h-5 w-5" />
                    J'ai consulté — terminer la leçon
                </button>
            )}
        </div>
    );
}
