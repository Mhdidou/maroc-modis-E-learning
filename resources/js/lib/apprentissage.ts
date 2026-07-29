import axios from 'axios';

/**
 * Client des endpoints du moteur pédagogique (réponses JSON). Le jeton CSRF est
 * injecté depuis le cookie XSRF-TOKEN, quelle que soit la version d'axios.
 * Toutes ces requêtes ne transmettent que des intentions : le serveur dispose.
 */
const api = axios.create({
    headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
});

api.interceptors.request.use((config) => {
    const m = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    if (m) {
        config.headers['X-XSRF-TOKEN'] = decodeURIComponent(m[1]);
    }
    return config;
});

export interface CheckpointResultat {
    correct: boolean;
    bonne_reponse: string;
    explication: string;
}

export interface QuestionTiree {
    id: number;
    enonce: string;
    options: string[];
}

export interface DemarrageQuiz {
    data: {
        id: number;
        module_id: number;
        numero: number;
        tentatives_max: number;
        duree_minutes: number | null;
        demarre_le: string;
    };
    questions: QuestionTiree[];
    tentatives_restantes: number;
}

export interface Correction {
    question_id: number;
    correct: boolean;
    bonne_reponse: string;
}

export interface ResultatQuiz {
    score: number;
    seuil: number;
    reussi: boolean;
    reset_chapitre: boolean;
    tentatives_restantes: number;
    corrections: Correction[];
}

export const soumettreCheckpoint = (checkpointId: number, reponse: string) =>
    api
        .post(route('apprentissage.checkpoints.soumettre', checkpointId), { reponse })
        .then((r) => r.data as CheckpointResultat);

export const terminerModule = (moduleId: number) =>
    api.post(route('apprentissage.modules.terminer', moduleId)).then((r) => r.data);

export const demarrerQuiz = (moduleId: number) =>
    api
        .post(route('apprentissage.quiz.demarrer', moduleId))
        .then((r) => r.data as DemarrageQuiz);

export const soumettreQuiz = (
    tentativeId: number,
    reponses: Record<number, string>,
    confirmation: boolean,
) =>
    api
        .post(route('apprentissage.quiz.soumettre', tentativeId), {
            reponses,
            confirmation,
        })
        .then((r) => r.data as ResultatQuiz);

export const soumettreDevoir = (moduleId: number, formData: FormData) =>
    api
        .post(route('apprentissage.devoir.soumettre', moduleId), formData)
        .then((r) => r.data);
