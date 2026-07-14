/**
 * Charte graphique centralisée du LMS Maroc-Modis (Groupe Triumph).
 * Réutilisée par la page d'accueil, les pages d'authentification et les espaces.
 */
export const BRAND = {
    red: '#E23744',
    blue: '#1C9AD6',
    ink: '#1B2430',
} as const;

// Logo officiel servi à la racine du site (public/images/).
export const LOGO_SRC = '/images/maroc-modis-logo.png';

import type { Role } from '@/types';

/**
 * Libellés et couleurs d'accent associés à chaque rôle.
 */
export const ROLE_META: Record<
    Role,
    { label: string; espace: string; accent: string }
> = {
    // L'admin est le rôle supérieur : accent indigo distinctif.
    admin: { label: 'Administrateur du site', espace: 'Espace Administration', accent: '#4338CA' },
    superviseur: { label: 'Superviseur', espace: 'Espace Superviseur', accent: BRAND.ink },
    formateur: { label: 'Formateur', espace: 'Espace Formateur', accent: BRAND.blue },
    apprenant: { label: 'Apprenant', espace: 'Espace Apprenant', accent: BRAND.red },
};
