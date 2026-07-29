/** Types du lecteur apprenant (payload de LecteurController@formation). */
export type StatutModule = 'non_commencee' | 'en_cours' | 'terminee';
export type ModuleType = 'pdf' | 'video' | 'quiz' | 'devoir';

export interface CheckpointLecture {
    id: number;
    position_secondes: number;
    enonce: string;
    options: string[];
}

export interface DevoirLecture {
    id: number;
    statut: 'en_attente' | 'approuve' | 'rejete';
    commentaire: string | null;
    a_fichier: boolean;
    nom_fichier: string | null;
}

export interface ModuleLecture {
    id: number;
    type: ModuleType;
    titre: string;
    position: number;
    statut: StatutModule;
    score: number | null;
    accessible: boolean;
    termine: boolean;
    // pdf / video
    contenu?: string | null;
    // video
    checkpoints?: CheckpointLecture[];
    resolus?: number[];
    // quiz
    seuil_reussite?: number | null;
    duree_minutes?: number | null;
    nb_questions_tirees?: number | null;
    banque_total?: number;
    tentatives_utilisees?: number;
    tentatives_max?: number;
    reussi?: boolean;
    tentative_ouverte_id?: number | null;
    // devoir
    consignes?: string | null;
    /** Pièce jointe d'explication facultative (vidéo ou PDF) déposée par le formateur. */
    piece_jointe?: string | null;
    piece_jointe_type?: 'video' | 'pdf' | null;
    devoir?: DevoirLecture | null;
}

export interface ChapitreLecture {
    id: number;
    titre: string;
    modules: ModuleLecture[];
}

export interface FormationLecture {
    id: number;
    titre: string;
    type: string;
    statut: string;
    chapitres: ChapitreLecture[];
}
