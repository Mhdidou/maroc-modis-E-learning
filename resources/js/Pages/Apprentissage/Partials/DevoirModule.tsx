import { soumettreDevoir } from '@/lib/apprentissage';
import { useMutation } from '@tanstack/react-query';
import {
    ClipboardCheck,
    Clock,
    FileText,
    Paperclip,
    ThumbsDown,
    ThumbsUp,
} from 'lucide-react';
import { useState } from 'react';
import { ModuleLecture } from '../types';

const champ =
    'block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-[#1C9AD6] focus:outline-none focus:ring-2 focus:ring-[#1C9AD6]/30';

/**
 * Devoir : l'apprenant soumet (texte et/ou fichier). La complétion du module
 * dépend de l'APPROBATION serveur (formateur/superviseur), jamais du client.
 */
export default function DevoirModule({
    module,
    onDone,
}: {
    module: ModuleLecture;
    onDone: () => void;
}) {
    const [texte, setTexte] = useState('');
    const [fichier, setFichier] = useState<File | null>(null);
    const [erreur, setErreur] = useState<string | null>(null);
    const devoir = module.devoir;

    const soumission = useMutation({
        mutationFn: () => {
            const fd = new FormData();
            if (texte) fd.append('contenu_texte', texte);
            if (fichier) fd.append('fichier', fichier);
            return soumettreDevoir(module.id, fd);
        },
        onSuccess: () => onDone(),
        onError: () =>
            setErreur(
                'Fournissez un texte ou un fichier. Tous les formats sont acceptés, jusqu’à 100 Mo.',
            ),
    });

    return (
        <div className="space-y-4 rounded-2xl border border-slate-100 bg-white p-6">
            <h3 className="flex items-center gap-2 text-lg font-bold">
                <ClipboardCheck className="h-5 w-5 text-[#D97706]" />
                {module.titre}
            </h3>

            {module.consignes && (
                <div className="whitespace-pre-line rounded-xl bg-slate-50 p-4 text-sm text-slate-700">
                    {module.consignes}
                </div>
            )}

            {/* Explication facultative déposée par le formateur : vidéo ou PDF. */}
            {module.piece_jointe && module.piece_jointe_type === 'video' && (
                <div className="space-y-2">
                    <p className="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-slate-400">
                        <Paperclip className="h-3.5 w-3.5" />
                        Explication du formateur
                    </p>
                    <video
                        src={module.piece_jointe}
                        controls
                        className="w-full rounded-xl bg-black"
                    />
                </div>
            )}

            {module.piece_jointe && module.piece_jointe_type === 'pdf' && (
                <a
                    href={module.piece_jointe}
                    target="_blank"
                    rel="noreferrer"
                    className="flex items-center gap-2 rounded-xl border border-slate-200 p-3 text-sm font-semibold text-[#1C9AD6] transition hover:bg-slate-50"
                >
                    <FileText className="h-4 w-4" />
                    Ouvrir le sujet (PDF)
                </a>
            )}

            {/* État de la dernière soumission */}
            {devoir && (
                <div
                    className={`flex items-start gap-2 rounded-xl p-3 text-sm ${
                        devoir.statut === 'approuve'
                            ? 'bg-green-50 text-green-700'
                            : devoir.statut === 'rejete'
                              ? 'bg-red-50 text-[#E23744]'
                              : 'bg-amber-50 text-amber-800'
                    }`}
                >
                    {devoir.statut === 'approuve' ? (
                        <ThumbsUp className="mt-0.5 h-4 w-4 shrink-0" />
                    ) : devoir.statut === 'rejete' ? (
                        <ThumbsDown className="mt-0.5 h-4 w-4 shrink-0" />
                    ) : (
                        <Clock className="mt-0.5 h-4 w-4 shrink-0" />
                    )}
                    <span>
                        {devoir.statut === 'approuve' &&
                            'Devoir approuvé — leçon validée.'}
                        {devoir.statut === 'rejete' &&
                            'Devoir rejeté. Vous pouvez soumettre à nouveau.'}
                        {devoir.statut === 'en_attente' &&
                            'En attente de correction par votre formateur.'}
                        {devoir.commentaire && (
                            <span className="mt-1 block italic">
                                « {devoir.commentaire} »
                            </span>
                        )}
                        {devoir.a_fichier && (
                            <a
                                href={route(
                                    'apprentissage.devoir.fichier',
                                    devoir.id,
                                )}
                                className="mt-1 flex items-center gap-1.5 font-semibold underline underline-offset-2"
                            >
                                <Paperclip className="h-3.5 w-3.5" />
                                {devoir.nom_fichier ?? 'Votre fichier rendu'}
                            </a>
                        )}
                    </span>
                </div>
            )}

            {/* Formulaire de (re)soumission : masqué si en attente ou approuvé */}
            {(!devoir ||
                devoir.statut === 'rejete') && (
                <div className="space-y-3">
                    <textarea
                        value={texte}
                        onChange={(e) => setTexte(e.target.value)}
                        rows={4}
                        className={champ}
                        placeholder="Votre réponse (texte)…"
                    />
                    <input
                        type="file"
                        onChange={(e) => setFichier(e.target.files?.[0] ?? null)}
                        className="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-[#1C9AD6] file:px-3 file:py-2 file:text-sm file:font-semibold file:text-white"
                    />
                    {erreur && <p className="text-xs text-[#E23744]">{erreur}</p>}
                    <button
                        disabled={
                            soumission.isPending || (!texte && !fichier)
                        }
                        onClick={() => {
                            setErreur(null);
                            soumission.mutate();
                        }}
                        className="rounded-xl bg-[#D97706] px-5 py-2.5 text-sm font-bold text-white disabled:opacity-50"
                    >
                        Soumettre le devoir
                    </button>
                </div>
            )}
        </div>
    );
}
