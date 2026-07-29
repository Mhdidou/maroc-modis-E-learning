import { Head, Link } from '@inertiajs/react';
import { AlertTriangle, ArrowLeft, Download, Printer } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

type Statut = 'valide' | 'bientot_expire' | 'expire';

type Certificat = {
    id: number;
    numero_unique: string;
    titre_formation: string;
    statut: Statut;
    expire_le: string | null;
};

// A4 paysage à 96 dpi : le document a une taille physique fixe (voir le
// gabarit), on le met à l'échelle plutôt que de le laisser se déformer.
const LARGEUR_PX = 1123;
const HAUTEUR_PX = 794;

/**
 * Aperçu d'un certificat. Cette page ne dessine PAS le certificat : le document
 * est rendu côté serveur par un gabarit unique (resources/views/certificats/
 * modele.blade.php) et injecté ici en iframe. L'écran, l'impression et le PDF
 * téléchargé sont donc le même artefact — impossible qu'ils divergent.
 */
export default function Apercu({
    certificat,
    documentHtml,
}: {
    certificat: Certificat;
    documentHtml: string;
}) {
    const cadre = useRef<HTMLIFrameElement>(null);
    const conteneur = useRef<HTMLDivElement>(null);
    const [echelle, setEchelle] = useState(1);

    // Le document garde ses cotes A4 ; on le réduit pour tenir dans la largeur
    // disponible (tablette d'atelier comprise), sans jamais l'agrandir.
    useEffect(() => {
        const element = conteneur.current;
        if (!element) return;

        const observateur = new ResizeObserver(() => {
            setEchelle(Math.min(1, element.clientWidth / LARGEUR_PX));
        });

        observateur.observe(element);

        return () => observateur.disconnect();
    }, []);

    // On imprime le document seul, pas la page qui l'entoure.
    const imprimer = () => cadre.current?.contentWindow?.print();

    return (
        <div className="min-h-screen bg-[#F4F2ED] py-8 font-sans text-[#1B2430] antialiased">
            <Head title={`Certificat — ${certificat.titre_formation}`} />

            <div className="mx-auto mb-6 flex w-full max-w-[1123px] flex-wrap items-center justify-between gap-3 px-4">
                <Link
                    href={route('dashboard')}
                    className="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-600 hover:text-[#1B2430]"
                >
                    <ArrowLeft className="h-4 w-4" />
                    Retour
                </Link>

                <div className="flex items-center gap-2">
                    <button
                        onClick={imprimer}
                        className="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-bold text-[#1B2430] transition hover:bg-slate-50 active:scale-95"
                    >
                        <Printer className="h-4 w-4" />
                        Imprimer
                    </button>
                    <a
                        href={route('certificats.telecharger', certificat.id)}
                        className="inline-flex items-center gap-2 rounded-xl bg-[#1B2430] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-black active:scale-95"
                    >
                        <Download className="h-4 w-4" />
                        Télécharger le PDF
                    </a>
                </div>
            </div>

            {/* Alerte de validité — sur la page, pas sur le document. */}
            {certificat.statut !== 'valide' && (
                <div className="mx-auto mb-6 w-full max-w-[1123px] px-4">
                    <div
                        className={`flex items-start gap-3 rounded-xl border px-4 py-3 text-sm ${
                            certificat.statut === 'expire'
                                ? 'border-red-200 bg-red-50 text-red-800'
                                : 'border-amber-200 bg-amber-50 text-amber-800'
                        }`}
                    >
                        <AlertTriangle className="mt-0.5 h-4 w-4 shrink-0" />
                        <p>
                            {certificat.statut === 'expire' ? (
                                <>
                                    <span className="font-bold">
                                        Ce certificat a expiré
                                    </span>{' '}
                                    le {certificat.expire_le}. Repassez la
                                    formation pour le renouveler.
                                </>
                            ) : (
                                <>
                                    <span className="font-bold">
                                        Ce certificat arrive à échéance
                                    </span>{' '}
                                    le {certificat.expire_le}. Pensez à planifier
                                    son renouvellement.
                                </>
                            )}
                        </p>
                    </div>
                </div>
            )}

            <div className="mx-auto w-full max-w-[1123px] px-4">
                <div
                    ref={conteneur}
                    style={{ height: HAUTEUR_PX * echelle }}
                    className="overflow-hidden shadow-[0_10px_40px_-12px_rgba(27,36,48,0.35)]"
                >
                    <iframe
                        ref={cadre}
                        title="Certificat"
                        srcDoc={documentHtml}
                        width={LARGEUR_PX}
                        height={HAUTEUR_PX}
                        style={{
                            transform: `scale(${echelle})`,
                            transformOrigin: 'top left',
                        }}
                        className="block border-0"
                        // Document statique issu de notre propre gabarit : aucun
                        // script à l'intérieur (allow-scripts reste désactivé).
                        // allow-same-origin est nécessaire pour déclencher
                        // l'impression depuis la page, allow-modals pour print().
                        sandbox="allow-same-origin allow-modals"
                    />
                </div>

                <p className="mt-4 text-center text-xs text-slate-500">
                    Vérifiable en ligne — N°{' '}
                    <span className="font-mono">{certificat.numero_unique}</span>
                </p>
            </div>
        </div>
    );
}
