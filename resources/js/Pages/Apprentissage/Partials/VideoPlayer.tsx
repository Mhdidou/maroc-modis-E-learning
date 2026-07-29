import {
    CheckpointResultat,
    soumettreCheckpoint,
    terminerModule,
} from '@/lib/apprentissage';
import { useMutation } from '@tanstack/react-query';
import { CheckCircle2, Lock, Play, Rewind, VideoOff } from 'lucide-react';
import Plyr from 'plyr';
import 'plyr/dist/plyr.css';
import { useEffect, useMemo, useRef, useState } from 'react';
import { CheckpointLecture, ModuleLecture } from '../types';

/** Secondes -> mm:ss, pour situer un point de contrôle dans la vidéo. */
const horodatage = (secondes: number) => {
    const m = Math.floor(secondes / 60);
    const s = Math.floor(secondes % 60);

    return `${m}:${String(s).padStart(2, '0')}`;
};

/**
 * Lecteur vidéo enrichi Plyr.
 *
 * Navigation LIBRE : l'apprenant se déplace où il veut dans la vidéo, en avant
 * comme en arrière. Ce n'est pas la lecture linéaire qui garantit
 * l'apprentissage, c'est la validation finale.
 *
 * Ce qui verrouille la complétion :
 *  - TOUS les points de contrôle doivent être répondus (vérifié côté serveur
 *    par MoteurCompletion : sauter un passage ne les fait pas disparaître) ;
 *  - la vidéo doit avoir été menée jusqu'à son terme.
 *
 * Un point de contrôle croisé pendant la lecture met la vidéo en pause SUR
 * PLACE et impose sa question. Ceux qu'on a sautés restent dus : la liste sous
 * le lecteur les signale et permet d'y aller directement.
 */
export default function VideoPlayer({
    module,
    onDone,
}: {
    module: ModuleLecture;
    onDone: () => void;
}) {
    const videoRef = useRef<HTMLVideoElement>(null);
    const conteneurRef = useRef<HTMLDivElement>(null);
    const checkpoints = useMemo(() => module.checkpoints ?? [], [module]);

    const [resolus, setResolus] = useState<Set<number>>(
        () => new Set(module.resolus ?? []),
    );
    const [actif, setActif] = useState<CheckpointLecture | null>(null);
    const [choix, setChoix] = useState('');
    const [confirme, setConfirme] = useState(false);
    const [resultat, setResultat] = useState<CheckpointResultat | null>(null);
    const [finie, setFinie] = useState(false);
    const [erreurMedia, setErreurMedia] = useState(false);

    // Refs pour les handlers d'événements (évite les closures périmées).
    const resolusRef = useRef(resolus);
    const actifRef = useRef(actif);
    resolusRef.current = resolus;
    actifRef.current = actif;

    // Position au `timeupdate` précédent : sert à distinguer une lecture qui
    // avance seconde après seconde d'un saut dans la timeline.
    const tempsPrecedentRef = useRef(0);

    // Un saut est en cours : aucun point de contrôle ne doit se déclencher tant
    // qu'il n'est pas terminé. L'écart de temps ne suffit pas comme indice —
    // un petit saut (molette, léger glissement) ressemble à une lecture normale.
    const sautRef = useRef(false);

    useEffect(() => {
        const v = videoRef.current;
        if (!v) return;

        const player = new Plyr(v, {
            // Navigation complète : l'apprenant va où il veut dans la vidéo.
            controls: [
                'play',
                'rewind',
                'fast-forward',
                'progress',
                'current-time',
                'duration',
                'mute',
                'volume',
                'fullscreen',
            ],
            seekTime: 10,
            keyboard: { focused: false, global: false },
        });

        const onTime = () => {
            const t = v.currentTime;
            const precedent = tempsPrecedentRef.current;
            tempsPrecedentRef.current = t;

            // Pause sur un checkpoint, ou saut en cours : on ne déclenche rien.
            if (actifRef.current || sautRef.current) return;

            // Un point de contrôle ne se déclenche QUE si la lecture le
            // traverse réellement. L'ancienne condition
            // (`position <= t`) rattrapait tout contrôle situé en amont : au
            // moindre saut, le pop-up surgissait aussitôt et rendait toute
            // avance impossible.
            //
            // Entre deux `timeupdate` la lecture progresse de ~0,25 s : au-delà
            // d'une seconde et demie, ou en arrière, c'est un saut — on ne
            // déclenche rien, on se contente de mémoriser la position.
            const avanceNaturelle = t > precedent && t - precedent < 1.5;
            if (!avanceNaturelle) return;

            const cp = checkpoints.find(
                (c) =>
                    !resolusRef.current.has(c.id) &&
                    c.position_secondes > precedent &&
                    c.position_secondes <= t,
            );

            if (cp) {
                // Pause SUR PLACE : repositionner la lecture sur le checkpoint
                // donnait l'impression que la vidéo repartait de zéro.
                v.pause();
                setActif(cp);
                setChoix('');
                setConfirme(false);
                setResultat(null);
            }
        };

        const onSeeking = () => {
            sautRef.current = true;
        };

        // Après un saut, on repart de la nouvelle position : sans cela, l'écart
        // avec l'ancienne serait pris pour une lecture et déclencherait les
        // contrôles enjambés.
        const onSeeked = () => {
            tempsPrecedentRef.current = v.currentTime;
            sautRef.current = false;
        };

        const onEnded = () => setFinie(true);

        // Molette = saut de 5 s. Plyr ne l'implémente pas (la molette n'agit que
        // sur le volume) : on l'ajoute sur le conteneur, car c'est le geste
        // naturel pour revenir de quelques secondes sur un passage mal compris.
        const conteneur = conteneurRef.current;
        const onWheel = (e: WheelEvent) => {
            if (actifRef.current || !v.duration) return;
            e.preventDefault();

            const pas = e.deltaY > 0 ? -5 : 5;
            v.currentTime = Math.min(
                Math.max(v.currentTime + pas, 0),
                v.duration,
            );
        };

        v.addEventListener('timeupdate', onTime);
        v.addEventListener('seeking', onSeeking);
        v.addEventListener('seeked', onSeeked);
        v.addEventListener('ended', onEnded);
        conteneur?.addEventListener('wheel', onWheel, { passive: false });

        return () => {
            v.removeEventListener('timeupdate', onTime);
            v.removeEventListener('seeking', onSeeking);
            v.removeEventListener('seeked', onSeeked);
            v.removeEventListener('ended', onEnded);
            conteneur?.removeEventListener('wheel', onWheel);
            player.destroy();
        };
    }, [checkpoints]);

    // Changer la source d'un <video> ne suffit pas : le navigateur continue de
    // lire l'ancien média tant qu'on ne le recharge pas explicitement. Sans ce
    // load(), une vidéo fraîchement importée restait invisible.
    useEffect(() => {
        setErreurMedia(false);
        videoRef.current?.load();
    }, [module.contenu]);

    const soumission = useMutation({
        mutationFn: () => soumettreCheckpoint(actif!.id, choix),
        onSuccess: (data) => setResultat(data),
    });

    const completion = useMutation({
        mutationFn: () => terminerModule(module.id),
        onSuccess: () => onDone(),
    });

    const suivant = () => {
        if (!actif) return;
        const set = new Set(resolusRef.current);
        set.add(actif.id);
        setResolus(set);
        setActif(null);
        setConfirme(false);
        setResultat(null);
        videoRef.current?.play();
    };

    /**
     * Ferme la question sans y répondre. Le point de contrôle reste dû : il
     * repasse en rouge dans la liste et continue de bloquer la validation.
     * C'est la contrepartie de la navigation libre — on ne piège pas
     * l'apprenant dans un pop-up dont il ne peut pas sortir.
     */
    const reporter = () => {
        setActif(null);
        setChoix('');
        setConfirme(false);
        setResultat(null);
    };

    /**
     * Va au point de contrôle choisi. La navigation étant libre, un apprenant
     * peut avoir sauté un contrôle sans le voir : il doit pouvoir y revenir
     * sans re-parcourir la vidéo. Un contrôle non résolu ouvre directement sa
     * question ; un contrôle déjà répondu se contente de repositionner la
     * lecture pour revoir le passage.
     */
    const allerAuCheckpoint = (cp: CheckpointLecture) => {
        const v = videoRef.current;
        if (v) {
            v.currentTime = cp.position_secondes;
            v.pause();
        }

        if (resolus.has(cp.id)) return;

        setActif(cp);
        setChoix('');
        setConfirme(false);
        setResultat(null);
    };

    const tousResolus = checkpoints.every((c) => resolus.has(c.id));
    const peutTerminer = !module.termine && tousResolus && finie;

    return (
        <div className="space-y-4">
            <div
                ref={conteneurRef}
                className="relative overflow-hidden rounded-2xl bg-black"
            >
                {/* src porté par <video> (et non un <source> enfant) : c'est le
                    seul moyen que load() reprenne la nouvelle adresse. */}
                {/* eslint-disable-next-line jsx-a11y/media-has-caption */}
                <video
                    ref={videoRef}
                    src={module.contenu ?? undefined}
                    playsInline
                    preload="metadata"
                    className="w-full"
                    onError={() => setErreurMedia(true)}
                />

                {/* Média injoignable : sans ce message, l'apprenant n'a qu'un
                    rectangle noir et aucun moyen de comprendre. */}
                {erreurMedia && (
                    <div className="absolute inset-0 z-10 flex flex-col items-center justify-center gap-2 bg-[#1B2430] p-6 text-center">
                        <VideoOff className="h-8 w-8 text-slate-400" />
                        <p className="text-sm font-bold text-white">
                            Vidéo indisponible
                        </p>
                        <p className="text-xs text-slate-400">
                            Le fichier n'a pas pu être chargé. Signalez-le à votre
                            formateur.
                        </p>
                    </div>
                )}

                {/* Overlay bloquant du quiz-surprise */}
                {actif && (
                    <div className="absolute inset-0 z-10 flex items-center justify-center bg-[#1B2430]/90 p-4">
                        <div className="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl">
                            <p className="mb-1 text-xs font-bold uppercase tracking-wide text-[#1C9AD6]">
                                Question
                            </p>
                            <h4 className="mb-4 text-lg font-bold text-[#1B2430]">
                                {actif.enonce}
                            </h4>
                            <div className="space-y-2">
                                {actif.options.map((opt) => {
                                    let classe =
                                        'border-slate-200 hover:border-[#1C9AD6]';
                                    if (resultat) {
                                        if (opt === resultat.bonne_reponse)
                                            classe =
                                                'border-green-500 bg-green-50 text-green-800';
                                        else if (opt === choix)
                                            classe =
                                                'border-red-500 bg-red-50 text-red-800';
                                        else classe = 'border-slate-200 opacity-60';
                                    } else if (opt === choix) {
                                        classe = 'border-[#1C9AD6] bg-[#1C9AD6]/5';
                                    }
                                    return (
                                        <button
                                            key={opt}
                                            disabled={!!resultat}
                                            onClick={() => setChoix(opt)}
                                            className={`block w-full rounded-xl border px-4 py-2.5 text-left text-sm font-semibold transition ${classe}`}
                                        >
                                            {opt}
                                        </button>
                                    );
                                })}
                            </div>

                            {!resultat && (
                                <label className="mt-4 flex items-center gap-2 text-sm font-semibold text-[#1B2430]">
                                    <input
                                        type="checkbox"
                                        checked={confirme}
                                        onChange={(e) =>
                                            setConfirme(e.target.checked)
                                        }
                                    />
                                    Je confirme ma réponse.
                                </label>
                            )}

                            {resultat && (
                                <div className="mt-4 rounded-xl bg-slate-50 p-3 text-sm text-slate-700">
                                    <span
                                        className={`font-bold ${resultat.correct ? 'text-green-700' : 'text-[#E23744]'}`}
                                    >
                                        {resultat.correct
                                            ? 'Bonne réponse !'
                                            : 'Réponse incorrecte.'}
                                    </span>{' '}
                                    {resultat.explication}
                                </div>
                            )}

                            <div className="mt-5 flex items-center justify-between gap-3">
                                {/* Reporter la question : l'overlay recouvre le
                                    lecteur, sans cette issue l'apprenant est
                                    prisonnier du pop-up. Le contrôle reste dû —
                                    il réapparaît en rouge dans la liste et la
                                    leçon ne peut pas être validée sans lui. */}
                                {!resultat ? (
                                    <button
                                        onClick={reporter}
                                        className="rounded-xl px-3 py-2 text-sm font-semibold text-slate-500 hover:bg-slate-100"
                                    >
                                        Répondre plus tard
                                    </button>
                                ) : (
                                    <span />
                                )}

                                {resultat ? (
                                    <button
                                        onClick={suivant}
                                        className="inline-flex items-center gap-1.5 rounded-xl bg-[#1C9AD6] px-5 py-2.5 text-sm font-bold text-white"
                                    >
                                        <Play className="h-4 w-4" />
                                        Suivant
                                    </button>
                                ) : (
                                    <button
                                        disabled={
                                            !choix ||
                                            !confirme ||
                                            soumission.isPending
                                        }
                                        onClick={() => soumission.mutate()}
                                        className="rounded-xl bg-[#E23744] px-5 py-2.5 text-sm font-bold text-white disabled:opacity-60"
                                    >
                                        Valider
                                    </button>
                                )}
                            </div>
                        </div>
                    </div>
                )}
            </div>

            {/* Points de contrôle : état + accès direct. Indispensable depuis
                que la navigation est libre — un contrôle sauté doit rester
                trouvable, sinon la validation devient inatteignable. */}
            {checkpoints.length > 0 && (
                <div className="rounded-2xl border border-slate-100 bg-white p-4">
                    <p className="mb-3 flex items-center gap-2 text-xs font-semibold text-slate-500">
                        <Lock className="h-3.5 w-3.5" />
                        {resolus.size}/{checkpoints.length} point(s) de contrôle
                        répondu(s) — tous sont exigés pour valider la leçon.
                    </p>
                    <div className="flex flex-wrap gap-2">
                        {checkpoints.map((cp, i) => {
                            const fait = resolus.has(cp.id);

                            return (
                                <button
                                    key={cp.id}
                                    type="button"
                                    onClick={() => allerAuCheckpoint(cp)}
                                    className={`inline-flex items-center gap-1.5 rounded-xl border px-3 py-1.5 text-xs font-bold transition ${
                                        fait
                                            ? 'border-green-200 bg-green-50 text-green-700 hover:bg-green-100'
                                            : 'border-[#E23744]/30 bg-red-50 text-[#E23744] hover:bg-red-100'
                                    }`}
                                    title={
                                        fait
                                            ? 'Revoir ce passage'
                                            : 'Répondre à cette question'
                                    }
                                >
                                    {fait ? (
                                        <CheckCircle2 className="h-3.5 w-3.5" />
                                    ) : (
                                        <Lock className="h-3.5 w-3.5" />
                                    )}
                                    {i + 1} · {horodatage(cp.position_secondes)}
                                </button>
                            );
                        })}
                    </div>
                </div>
            )}

            <p className="flex items-center gap-2 text-xs text-slate-500">
                <Rewind className="h-3.5 w-3.5" />
                Naviguez librement : barre de progression, boutons ±10 s, ou
                molette de la souris sur la vidéo (±5 s). Pour valider, il faut
                l'avoir vue jusqu'au bout et avoir répondu à tous les points de
                contrôle.
            </p>

            {module.termine ? (
                <div className="inline-flex items-center gap-2 rounded-xl bg-green-50 px-4 py-2.5 text-sm font-bold text-green-700">
                    <CheckCircle2 className="h-5 w-5" />
                    Leçon terminée
                </div>
            ) : (
                <div className="space-y-2">
                    <button
                        disabled={!peutTerminer || completion.isPending}
                        onClick={() => completion.mutate()}
                        className="inline-flex items-center gap-2 rounded-xl bg-[#1B2430] px-5 py-2.5 text-sm font-bold text-white disabled:opacity-50"
                    >
                        <CheckCircle2 className="h-5 w-5" />
                        Terminer la leçon
                    </button>

                    {/* Ce qui manque encore, énoncé explicitement : un bouton
                        grisé sans motif est le meilleur moyen de bloquer un
                        apprenant qui a sauté un point de contrôle sans le voir. */}
                    {!peutTerminer && (
                        <p className="text-xs font-semibold text-[#E23744]">
                            {!tousResolus &&
                                `Il reste ${checkpoints.length - resolus.size} point(s) de contrôle à répondre — cliquez dessus ci-dessus pour y aller.`}
                            {tousResolus &&
                                !finie &&
                                'Regardez la vidéo jusqu’à la fin pour pouvoir valider.'}
                        </p>
                    )}
                </div>
            )}
        </div>
    );
}
