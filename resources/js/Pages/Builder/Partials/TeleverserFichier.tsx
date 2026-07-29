import { router, usePage } from '@inertiajs/react';
import {
    CheckCircle2,
    FileText,
    Paperclip,
    UploadCloud,
    Video,
} from 'lucide-react';
import { DragEvent, ReactNode, useRef, useState } from 'react';
import { BuilderModule } from '../types';

const VIDEO_ACCEPT = 'video/mp4,video/webm,video/ogg,.mov';
const PDF_ACCEPT = 'application/pdf';

/**
 * Réglages d'import par type de module. Le devoir accepte les deux familles :
 * le formateur explique l'énoncé au choix par une vidéo ou par un PDF, et cette
 * pièce jointe reste facultative (les consignes écrites peuvent suffire).
 */
const CONFIG: Record<
    string,
    { label: string; accept: string; hint: string; icone: ReactNode }
> = {
    video: {
        label: 'Fichier vidéo',
        accept: VIDEO_ACCEPT,
        hint: 'mp4, webm, ogg, mov — 500 Mo max',
        icone: <Video className="h-7 w-7 text-[#1C9AD6]" />,
    },
    pdf: {
        label: 'Fichier PDF',
        accept: PDF_ACCEPT,
        hint: 'pdf — 20 Mo max',
        icone: <FileText className="h-7 w-7 text-[#E23744]" />,
    },
    devoir: {
        label: "Pièce jointe d'explication (facultative)",
        accept: `${VIDEO_ACCEPT},${PDF_ACCEPT}`,
        hint: 'vidéo (mp4, webm, ogg, mov) ou pdf — 500 Mo max',
        icone: <Paperclip className="h-7 w-7 text-[#D97706]" />,
    },
};

/**
 * Import de fichier par glisser-déposer OU sélecteur de fichiers (explorateur),
 * à la place d'une saisie de chemin. Le fichier part vers le disque public ;
 * le serveur met à jour `contenu` avec son URL.
 */
const enMo = (octets: number) => Math.floor(octets / (1024 * 1024));

export default function TeleverserFichier({ module }: { module: BuilderModule }) {
    const config = CONFIG[module.type] ?? CONFIG.pdf;
    const inputRef = useRef<HTMLInputElement>(null);
    const [survol, setSurvol] = useState(false);
    const [envoi, setEnvoi] = useState(false);
    const [progression, setProgression] = useState(0);
    const [erreur, setErreur] = useState<string | null>(null);

    // Limite réellement appliquée par PHP (partagée par le serveur).
    const limiteOctets =
        usePage().props.limites?.upload_octets ?? 0;

    // Nature du média rattaché, déduite de l'extension : un module devoir
    // accepte indifféremment une vidéo ou un PDF.
    const extension = module.contenu?.split('.').pop()?.toLowerCase() ?? '';
    const apercu = ['mp4', 'webm', 'ogg', 'mov'].includes(extension)
        ? 'video'
        : extension === 'pdf'
          ? 'pdf'
          : null;

    const envoyer = (fichier: File) => {
        setErreur(null);

        // Contrôle AVANT envoi : au-delà de `post_max_size`, PHP vide la requête
        // et le serveur ne peut plus rattacher l'erreur au formulaire. Mieux
        // vaut refuser tout de suite avec un message exact.
        if (limiteOctets > 0 && fichier.size > limiteOctets) {
            setErreur(
                `Ce fichier fait ${enMo(fichier.size)} Mo — le serveur n'accepte ` +
                    `pas plus de ${enMo(limiteOctets)} Mo.`,
            );
            return;
        }

        router.post(
            route('builder.modules.fichier', module.id),
            { fichier },
            {
                forceFormData: true,
                preserveScroll: true,
                onStart: () => setEnvoi(true),
                onProgress: (e) => e && setProgression(Math.round(e.percentage ?? 0)),
                onError: (errs) =>
                    setErreur(errs.fichier ?? "Échec de l'import."),
                onFinish: () => {
                    setEnvoi(false);
                    setProgression(0);
                },
            },
        );
    };

    const onDrop = (e: DragEvent) => {
        e.preventDefault();
        setSurvol(false);
        const fichier = e.dataTransfer.files?.[0];
        if (fichier) envoyer(fichier);
    };

    return (
        <div>
            <label className="mb-1.5 block text-sm font-semibold">
                {config.label}
            </label>

            {/* Fichier actuellement rattaché. On affiche son NOM : sans cela un
                fichier de démonstration issu du seed passait pour un import
                réussi, et un import échoué restait invisible. */}
            {module.contenu && (
                <div className="mb-2 flex items-center gap-2 rounded-lg bg-green-50 px-3 py-2 text-sm text-green-700">
                    <CheckCircle2 className="h-4 w-4 shrink-0" />
                    <span className="flex-1 truncate">
                        Fichier actuel :{' '}
                        <a
                            href={module.contenu}
                            target="_blank"
                            rel="noreferrer"
                            className="font-semibold underline"
                            title={module.contenu}
                        >
                            {module.contenu.split('/').pop()}
                        </a>
                    </span>
                </div>
            )}

            {/* Aperçu réel du média importé. Le formateur doit VOIR sa vidéo,
                pas seulement un lien vers son URL. Lecteur HTML natif : aucune
                mesure anti-triche ici, on est côté auteur et il doit pouvoir
                naviguer librement dans son propre contenu. */}
            {module.contenu && apercu === 'video' && (
                <video
                    key={module.contenu}
                    src={module.contenu}
                    controls
                    playsInline
                    className="mb-3 max-h-72 w-full rounded-xl bg-black"
                    onError={() =>
                        setErreur(
                            'La vidéo est enregistrée mais illisible à cette adresse. ' +
                                'Vérifiez que le lien storage existe (php artisan storage:link).',
                        )
                    }
                />
            )}

            {module.contenu && apercu === 'pdf' && (
                <object
                    key={module.contenu}
                    data={module.contenu}
                    type="application/pdf"
                    className="mb-3 h-72 w-full rounded-xl border border-slate-200"
                >
                    <p className="p-4 text-sm text-slate-500">
                        Aperçu PDF indisponible dans ce navigateur —{' '}
                        <a
                            href={module.contenu}
                            target="_blank"
                            rel="noreferrer"
                            className="font-semibold underline"
                        >
                            ouvrir le fichier
                        </a>
                    </p>
                </object>
            )}

            {/* Zone glisser-déposer / sélecteur */}
            <div
                role="button"
                tabIndex={0}
                onClick={() => inputRef.current?.click()}
                onKeyDown={(e) =>
                    (e.key === 'Enter' || e.key === ' ') &&
                    inputRef.current?.click()
                }
                onDragOver={(e) => {
                    e.preventDefault();
                    setSurvol(true);
                }}
                onDragLeave={() => setSurvol(false)}
                onDrop={onDrop}
                className={`flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed px-4 py-6 text-center transition ${
                    survol
                        ? 'border-[#1C9AD6] bg-[#1C9AD6]/5'
                        : 'border-slate-300 hover:border-[#1C9AD6]'
                }`}
            >
                {config.icone}
                <p className="text-sm font-semibold text-[#1B2430]">
                    Glissez le fichier ici
                </p>
                <p className="inline-flex items-center gap-1 text-xs text-slate-500">
                    <UploadCloud className="h-3.5 w-3.5" />
                    ou cliquez pour parcourir
                </p>
                <p className="text-[11px] text-slate-400">{config.hint}</p>

                <input
                    ref={inputRef}
                    type="file"
                    accept={config.accept}
                    className="hidden"
                    onChange={(e) => {
                        const f = e.target.files?.[0];
                        if (f) envoyer(f);
                        e.target.value = '';
                    }}
                />
            </div>

            {envoi && (
                <div className="mt-2">
                    <div className="h-2 w-full overflow-hidden rounded-full bg-slate-100">
                        <div
                            className="h-full bg-[#1C9AD6] transition-all"
                            style={{ width: `${progression}%` }}
                        />
                    </div>
                    <p className="mt-1 text-xs text-slate-500">
                        Import en cours… {progression}%
                    </p>
                </div>
            )}

            {erreur && <p className="mt-1 text-xs text-[#E23744]">{erreur}</p>}
        </div>
    );
}
