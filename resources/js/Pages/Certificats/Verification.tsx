import { BRAND, LOGO_SRC } from '@/brand';
import { Head } from '@inertiajs/react';
import { BadgeCheck, CircleSlash, Clock, XCircle } from 'lucide-react';

type Statut = 'valide' | 'bientot_expire' | 'expire';

type Certificat = {
    apprenant: string;
    domaine: string | null;
    formation: string;
    certifiante: boolean;
    delivre_le: string | null;
    expire_le: string | null;
    statut: Statut;
};

/**
 * Page publique de vérification d'un certificat (cible du QR code). Un
 * employeur ou un auditeur confirme l'authenticité d'un document sans compte.
 *
 * N'affiche que le strict nécessaire : nom, formation, dates, validité. Jamais
 * l'e-mail, le rôle ni l'identifiant interne.
 */
export default function Verification({
    numero,
    certificat,
}: {
    numero: string;
    certificat: Certificat | null;
}) {
    return (
        <div className="flex min-h-screen flex-col items-center justify-center bg-[#F4F2ED] px-4 py-12 font-sans text-[#1B2430] antialiased">
            <Head title={`Vérification — ${numero}`} />

            <img src={LOGO_SRC} alt="Maroc-Modis" className="h-12 w-auto" />

            <p className="mt-4 text-xs font-bold uppercase tracking-[0.28em] text-[#8C6B3F]">
                Vérification de certificat
            </p>

            <div className="mt-8 w-full max-w-lg rounded-2xl border border-[#E3DED2] bg-white p-8 shadow-sm">
                {certificat === null ? (
                    <Introuvable numero={numero} />
                ) : (
                    <Resultat numero={numero} certificat={certificat} />
                )}
            </div>

            <p className="mt-6 max-w-lg text-center text-xs leading-relaxed text-slate-500">
                Maroc-Modis · Groupe Triumph — Ce service confirme l'existence et
                la validité d'un certificat interne de formation.
            </p>
        </div>
    );
}

function Introuvable({ numero }: { numero: string }) {
    return (
        <div className="text-center">
            <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100">
                <CircleSlash className="h-6 w-6 text-slate-400" />
            </div>
            <h1 className="mt-4 text-lg font-bold">Certificat introuvable</h1>
            <p className="mt-2 text-sm text-slate-600">
                Aucun certificat ne correspond au numéro{' '}
                <span className="font-mono text-[#1B2430]">{numero}</span>.
                Vérifiez la saisie ou le code scanné.
            </p>
        </div>
    );
}

function Resultat({
    numero,
    certificat,
}: {
    numero: string;
    certificat: Certificat;
}) {
    const etat = {
        valide: {
            Icone: BadgeCheck,
            titre: 'Certificat authentique',
            classeCercle: 'bg-emerald-50',
            classeIcone: 'text-emerald-600',
        },
        bientot_expire: {
            Icone: Clock,
            titre: 'Authentique — échéance proche',
            classeCercle: 'bg-amber-50',
            classeIcone: 'text-amber-600',
        },
        expire: {
            Icone: XCircle,
            titre: 'Authentique mais expiré',
            classeCercle: 'bg-red-50',
            classeIcone: 'text-red-600',
        },
    }[certificat.statut];

    const { Icone } = etat;

    return (
        <div>
            <div className="text-center">
                <div
                    className={`mx-auto flex h-12 w-12 items-center justify-center rounded-full ${etat.classeCercle}`}
                >
                    <Icone className={`h-6 w-6 ${etat.classeIcone}`} />
                </div>
                <h1 className="mt-4 text-lg font-bold">{etat.titre}</h1>
            </div>

            <dl className="mt-8 space-y-4 text-sm">
                <Ligne etiquette="Titulaire">
                    <span className="font-bold">{certificat.apprenant}</span>
                    {certificat.domaine && (
                        <span className="block text-xs text-slate-500">
                            Atelier {certificat.domaine}
                        </span>
                    )}
                </Ligne>

                <Ligne etiquette="Formation">
                    <span className="font-semibold">{certificat.formation}</span>
                    <span className="block text-xs text-slate-500">
                        {certificat.certifiante
                            ? 'Formation certifiante'
                            : 'Formation'}
                    </span>
                </Ligne>

                <Ligne etiquette="Délivré le">
                    {certificat.delivre_le ?? '—'}
                </Ligne>

                {certificat.expire_le && (
                    <Ligne etiquette="Valable jusqu'au">
                        <span
                            className={
                                certificat.statut === 'expire'
                                    ? 'font-semibold text-red-700'
                                    : undefined
                            }
                        >
                            {certificat.expire_le}
                        </span>
                    </Ligne>
                )}

                <Ligne etiquette="Numéro">
                    <span className="font-mono text-xs">{numero}</span>
                </Ligne>
            </dl>

            <div
                className="mt-8 h-[3px] w-full"
                style={{
                    background: `linear-gradient(90deg, ${BRAND.red} 0 50%, ${BRAND.blue} 50% 100%)`,
                }}
            />
        </div>
    );
}

function Ligne({
    etiquette,
    children,
}: {
    etiquette: string;
    children: React.ReactNode;
}) {
    return (
        <div className="flex justify-between gap-6 border-b border-slate-100 pb-3">
            <dt className="shrink-0 text-xs font-semibold uppercase tracking-wide text-slate-400">
                {etiquette}
            </dt>
            <dd className="text-right">{children}</dd>
        </div>
    );
}
