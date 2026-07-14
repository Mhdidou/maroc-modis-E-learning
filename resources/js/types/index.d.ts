export type Role = 'admin' | 'superviseur' | 'formateur' | 'apprenant';

export interface User {
    id: number;
    name: string;
    email: string;
    role: Role;
    domaine?: string | null;
    superviseur_id?: number | null;
    email_verified_at?: string;
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User;
    };
    flash: {
        status?: string | null;
    };
};
