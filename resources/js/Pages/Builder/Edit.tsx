import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {
    DndContext,
    DragEndEvent,
    PointerSensor,
    closestCenter,
    useSensor,
    useSensors,
} from '@dnd-kit/core';
import {
    SortableContext,
    arrayMove,
    verticalListSortingStrategy,
} from '@dnd-kit/sortable';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { ArrowLeft, Eye, EyeOff, Plus, Trash2, UserCheck } from 'lucide-react';
import { useEffect, useState } from 'react';
import { PageProps } from '@/types';
import ModuleEditor from './Partials/ModuleEditor';
import SortableChapitre from './Partials/SortableChapitre';
import { Responsable } from './Index';
import { BuilderChapitre, BuilderFormation, ModuleType } from './types';

export default function Edit({
    formation,
    responsables,
}: {
    formation: BuilderFormation;
    /** Fourni uniquement à l'admin, qui seul peut (ré)attribuer une formation. */
    responsables: Responsable[] | null;
}) {
    const estAdmin = responsables !== null;
    const flash = usePage<PageProps>().props.flash;
    const [chapitres, setChapitres] = useState<BuilderChapitre[]>(
        formation.chapitres,
    );
    const [selection, setSelection] = useState<number | null>(null);

    // Resynchronise l'état local avec la source de vérité serveur.
    useEffect(() => setChapitres(formation.chapitres), [formation]);

    const sensors = useSensors(
        useSensor(PointerSensor, { activationConstraint: { distance: 5 } }),
    );

    const meta = useForm({
        titre: formation.titre,
        description: formation.description ?? '',
        type: formation.type,
        validite_mois: formation.validite_mois ?? '',
        cree_par: formation.cree_par,
    });

    const nouveauChapitre = useForm({ titre: '' });

    const moduleSelectionne = chapitres
        .flatMap((c) => c.modules)
        .find((m) => m.id === selection);

    const reordonnerChapitres = (e: DragEndEvent) => {
        const { active, over } = e;
        if (!over || active.id === over.id) return;
        const ancien = chapitres.findIndex((c) => c.id === active.id);
        const nouveau = chapitres.findIndex((c) => c.id === over.id);
        const ordonnes = arrayMove(chapitres, ancien, nouveau);
        setChapitres(ordonnes);
        router.post(
            route('builder.chapitres.reordonner', formation.id),
            { ordre: ordonnes.map((c) => c.id) },
            { preserveScroll: true, preserveState: true },
        );
    };

    return (
        <AuthenticatedLayout
            header={
                <div>
                    <Link
                        href={route('builder.index')}
                        className="mb-2 inline-flex items-center gap-1 text-sm font-semibold text-[#1C9AD6] hover:underline"
                    >
                        <ArrowLeft className="h-4 w-4" />
                        Toutes les formations
                    </Link>
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <input
                            value={meta.data.titre}
                            onChange={(e) => meta.setData('titre', e.target.value)}
                            onBlur={() =>
                                meta.put(
                                    route('builder.formations.update', formation.id),
                                    { preserveScroll: true },
                                )
                            }
                            className="min-w-0 flex-1 rounded-lg border border-transparent bg-transparent px-1 text-2xl font-extrabold text-[#1B2430] hover:border-slate-200 focus:border-[#1C9AD6] focus:outline-none"
                        />
                        <div className="flex items-center gap-2">
                            <span
                                className={`rounded-full px-3 py-1 text-xs font-bold ${
                                    formation.statut === 'publie'
                                        ? 'bg-green-100 text-green-700'
                                        : 'bg-amber-100 text-amber-700'
                                }`}
                            >
                                {formation.statut === 'publie'
                                    ? 'Publiée'
                                    : 'Brouillon'}
                            </span>
                            {formation.statut === 'publie' ? (
                                <button
                                    onClick={() =>
                                        router.post(
                                            route(
                                                'builder.formations.depublier',
                                                formation.id,
                                            ),
                                            {},
                                            { preserveScroll: true },
                                        )
                                    }
                                    className="inline-flex items-center gap-1 rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-semibold text-slate-600 hover:bg-slate-50"
                                >
                                    <EyeOff className="h-4 w-4" />
                                    Dépublier
                                </button>
                            ) : (
                                <button
                                    onClick={() =>
                                        router.post(
                                            route(
                                                'builder.formations.publier',
                                                formation.id,
                                            ),
                                            {},
                                            { preserveScroll: true },
                                        )
                                    }
                                    className="inline-flex items-center gap-1 rounded-lg bg-[#1C9AD6] px-3 py-1.5 text-sm font-bold text-white hover:brightness-95"
                                >
                                    <Eye className="h-4 w-4" />
                                    Publier
                                </button>
                            )}
                            <button
                                onClick={() =>
                                    confirm(
                                        'Supprimer définitivement cette formation ?',
                                    ) &&
                                    router.delete(
                                        route(
                                            'builder.formations.destroy',
                                            formation.id,
                                        ),
                                    )
                                }
                                className="inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-sm font-semibold text-[#E23744] hover:bg-red-50"
                            >
                                <Trash2 className="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                </div>
            }
        >
            <Head title={`Atelier — ${formation.titre}`} />

            {flash.status && (
                <div className="mb-4 rounded-xl bg-green-50 px-4 py-2.5 text-sm font-semibold text-green-700">
                    {flash.status}
                </div>
            )}

            {/* Attribution : l'admin construit pour le compte d'un responsable
                pédagogique. Tant que la formation lui reste rattachée, elle ne
                peut pas être publiée. */}
            {estAdmin && (
                <div
                    className={`mb-4 rounded-xl border p-4 ${
                        formation.attribuee
                            ? 'border-slate-200 bg-white'
                            : 'border-[#E23744]/30 bg-red-50'
                    }`}
                >
                    <label className="mb-1.5 flex items-center gap-2 text-sm font-semibold">
                        <UserCheck className="h-4 w-4 text-[#1C9AD6]" />
                        Responsable de la formation
                    </label>
                    <div className="flex flex-wrap items-center gap-3">
                        <select
                            value={meta.data.cree_par}
                            onChange={(e) => {
                                // Payload explicite : `setData` est asynchrone,
                                // s'appuyer dessus enverrait l'ancien
                                // responsable.
                                const id = Number(e.target.value);
                                meta.setData('cree_par', id);
                                router.put(
                                    route(
                                        'builder.formations.update',
                                        formation.id,
                                    ),
                                    {
                                        titre: meta.data.titre,
                                        description: meta.data.description,
                                        type: meta.data.type,
                                        validite_mois: meta.data.validite_mois,
                                        cree_par: id,
                                    },
                                    { preserveScroll: true },
                                );
                            }}
                            className="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm focus:border-[#1C9AD6] focus:outline-none focus:ring-2 focus:ring-[#1C9AD6]/30"
                        >
                            {!formation.attribuee && (
                                <option value={formation.cree_par}>
                                    — Non attribuée —
                                </option>
                            )}
                            {responsables.map((r) => (
                                <option key={r.id} value={r.id}>
                                    {r.nom} ({r.role})
                                </option>
                            ))}
                        </select>
                        {formation.saisi_par && (
                            <span className="text-xs text-slate-500">
                                Saisie par {formation.saisi_par}
                            </span>
                        )}
                    </div>
                    {!formation.attribuee && (
                        <p className="mt-2 text-xs font-semibold text-[#E23744]">
                            Cette formation n'est attribuée à personne. Désignez
                            un formateur ou un superviseur avant de la publier —
                            c'est son nom qui figurera sur les certificats.
                        </p>
                    )}
                </div>
            )}

            <div className="grid gap-6 lg:grid-cols-2">
                {/* Colonne structure */}
                <section>
                    <DndContext
                        sensors={sensors}
                        collisionDetection={closestCenter}
                        onDragEnd={reordonnerChapitres}
                    >
                        <SortableContext
                            items={chapitres.map((c) => c.id)}
                            strategy={verticalListSortingStrategy}
                        >
                            <div className="space-y-4">
                                {chapitres.map((chapitre) => (
                                    <SortableChapitre
                                        key={chapitre.id}
                                        chapitre={chapitre}
                                        selection={selection}
                                        onSelectModule={setSelection}
                                        sensors={sensors}
                                        onReorderModules={(ordonnes) =>
                                            setChapitres((prev) =>
                                                prev.map((c) =>
                                                    c.id === chapitre.id
                                                        ? { ...c, modules: ordonnes }
                                                        : c,
                                                ),
                                            )
                                        }
                                        onAddModule={(type: ModuleType) =>
                                            router.post(
                                                route(
                                                    'builder.modules.store',
                                                    chapitre.id,
                                                ),
                                                {
                                                    type,
                                                    titre: 'Nouvelle leçon',
                                                },
                                                { preserveScroll: true },
                                            )
                                        }
                                    />
                                ))}
                            </div>
                        </SortableContext>
                    </DndContext>

                    {/* Ajout de chapitre */}
                    <div className="mt-4 flex items-center gap-2">
                        <input
                            value={nouveauChapitre.data.titre}
                            onChange={(e) =>
                                nouveauChapitre.setData('titre', e.target.value)
                            }
                            placeholder="Titre du nouveau module"
                            className="flex-1 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm focus:border-[#1C9AD6] focus:outline-none focus:ring-2 focus:ring-[#1C9AD6]/30"
                            onKeyDown={(e) => {
                                if (e.key === 'Enter' && nouveauChapitre.data.titre) {
                                    nouveauChapitre.post(
                                        route(
                                            'builder.chapitres.store',
                                            formation.id,
                                        ),
                                        {
                                            preserveScroll: true,
                                            onSuccess: () =>
                                                nouveauChapitre.reset(),
                                        },
                                    );
                                }
                            }}
                        />
                        <button
                            onClick={() =>
                                nouveauChapitre.post(
                                    route('builder.chapitres.store', formation.id),
                                    {
                                        preserveScroll: true,
                                        onSuccess: () => nouveauChapitre.reset(),
                                    },
                                )
                            }
                            disabled={
                                nouveauChapitre.processing ||
                                !nouveauChapitre.data.titre
                            }
                            className="inline-flex items-center gap-1 rounded-xl bg-[#E23744] px-4 py-2.5 text-sm font-bold text-white disabled:opacity-60"
                        >
                            <Plus className="h-4 w-4" />
                            Module
                        </button>
                    </div>
                </section>

                {/* Colonne édition du module sélectionné */}
                <section>
                    {moduleSelectionne ? (
                        <ModuleEditor
                            key={moduleSelectionne.id}
                            module={moduleSelectionne}
                        />
                    ) : (
                        <div className="rounded-2xl border border-dashed border-slate-200 bg-white p-10 text-center text-sm text-slate-500">
                            Sélectionnez un module à gauche pour l'éditer, ou
                            ajoutez-en un.
                        </div>
                    )}
                </section>
            </div>
        </AuthenticatedLayout>
    );
}
