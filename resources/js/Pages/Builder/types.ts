/**
 * Types partagés de l'atelier de construction de cours (formateur).
 * Reflètent le payload de FormationBuilderController@show.
 */
export type ModuleType = 'pdf' | 'video' | 'quiz' | 'devoir';
export type FormationType = 'certifiante' | 'non_certifiante';
export type FormationStatut = 'brouillon' | 'publie';

export interface Question {
    id: number;
    enonce: string;
    bonne_reponse: string;
    mauvaises_reponses: string[];
}

export interface Checkpoint {
    id: number;
    position_secondes: number;
    enonce: string;
    bonne_reponse: string;
    mauvaises_reponses: string[];
    explication: string;
}

export interface BuilderModule {
    id: number;
    type: ModuleType;
    titre: string;
    contenu: string | null;
    consignes: string | null;
    position: number;
    nb_questions_tirees: number | null;
    seuil_reussite: number | null;
    duree_minutes: number | null;
    questions: Question[];
    checkpoints: Checkpoint[];
}

export interface BuilderChapitre {
    id: number;
    titre: string;
    position: number;
    modules: BuilderModule[];
}

export interface BuilderFormation {
    id: number;
    titre: string;
    description: string | null;
    type: FormationType;
    statut: FormationStatut;
    validite_mois: number | null;
    /** Responsable pédagogique : son nom est attribué à la formation. */
    cree_par: number;
    responsable: string | null;
    /** Opérateur réel quand l'admin a saisi pour le compte du responsable. */
    saisi_par: string | null;
    attribuee: boolean;
    chapitres: BuilderChapitre[];
}

export const TYPE_MODULE_META: Record<
    ModuleType,
    { label: string; couleur: string }
> = {
    pdf: { label: 'Document PDF', couleur: '#E23744' },
    video: { label: 'Vidéo', couleur: '#1C9AD6' },
    quiz: { label: 'Quiz noté', couleur: '#7C3AED' },
    devoir: { label: 'Devoir', couleur: '#D97706' },
};
