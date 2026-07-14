import { LOGO_SRC, ROLE_META } from '@/brand';
import Dropdown from '@/Components/Dropdown';
import { Link, usePage } from '@inertiajs/react';
import { LayoutDashboard, LogOut, Menu, User as UserIcon, Users, X } from 'lucide-react';
import { PropsWithChildren, ReactNode, useState } from 'react';

/**
 * Layout des espaces authentifiés, aligné sur la charte Maroc-Modis.
 * La navigation s'adapte au rôle : le lien « Comptes » n'apparaît que
 * pour l'administrateur du site et les superviseurs.
 */
export default function Authenticated({
    header,
    children,
}: PropsWithChildren<{ header?: ReactNode }>) {
    const user = usePage().props.auth.user;
    const meta = ROLE_META[user.role];
    const peutGererComptes = user.role === 'admin' || user.role === 'superviseur';

    const [open, setOpen] = useState(false);

    const NavItem = ({
        href,
        active,
        icon: Icon,
        children,
    }: {
        href: string;
        active: boolean;
        icon: React.ElementType;
        children: ReactNode;
    }) => (
        <Link
            href={href}
            className={`inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-semibold transition ${
                active
                    ? 'bg-[#1B2430] text-white'
                    : 'text-slate-600 hover:bg-slate-100'
            }`}
        >
            <Icon className="h-4 w-4" />
            {children}
        </Link>
    );

    return (
        <div className="min-h-screen bg-slate-50 font-sans text-[#1B2430] antialiased">
            <nav className="sticky top-0 z-50 border-b border-slate-200 bg-white/90 backdrop-blur">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="flex h-16 items-center justify-between">
                        <div className="flex items-center gap-6">
                            <Link href="/" className="flex items-center gap-2">
                                <img
                                    src={LOGO_SRC}
                                    alt="Maroc-Modis"
                                    className="h-8 w-auto"
                                />
                                <span className="hidden text-base font-extrabold tracking-tight sm:inline">
                                    E-learning
                                </span>
                            </Link>

                            <div className="hidden items-center gap-1 md:flex">
                                <NavItem
                                    href={route('dashboard')}
                                    active={route().current('dashboard')}
                                    icon={LayoutDashboard}
                                >
                                    {meta.espace}
                                </NavItem>
                                {peutGererComptes && (
                                    <NavItem
                                        href={route('utilisateurs.index')}
                                        active={route().current('utilisateurs.*')}
                                        icon={Users}
                                    >
                                        Comptes
                                    </NavItem>
                                )}
                            </div>
                        </div>

                        <div className="flex items-center gap-3">
                            <span
                                className="hidden rounded-full px-3 py-1 text-xs font-bold text-white sm:inline"
                                style={{ backgroundColor: meta.accent }}
                            >
                                {meta.label}
                            </span>

                            <div className="hidden md:block">
                                <Dropdown>
                                    <Dropdown.Trigger>
                                        <button className="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">
                                            <UserIcon className="h-4 w-4" />
                                            {user.name}
                                        </button>
                                    </Dropdown.Trigger>
                                    <Dropdown.Content>
                                        <Dropdown.Link href={route('profile.edit')}>
                                            Mon profil
                                        </Dropdown.Link>
                                        <Dropdown.Link
                                            href={route('logout')}
                                            method="post"
                                            as="button"
                                        >
                                            Se déconnecter
                                        </Dropdown.Link>
                                    </Dropdown.Content>
                                </Dropdown>
                            </div>

                            <button
                                onClick={() => setOpen((v) => !v)}
                                className="inline-flex items-center justify-center rounded-lg p-2 text-slate-600 hover:bg-slate-100 md:hidden"
                                aria-label="Menu"
                            >
                                {open ? (
                                    <X className="h-6 w-6" />
                                ) : (
                                    <Menu className="h-6 w-6" />
                                )}
                            </button>
                        </div>
                    </div>
                </div>

                {/* Menu mobile */}
                {open && (
                    <div className="space-y-1 border-t border-slate-200 px-4 py-3 md:hidden">
                        <Link
                            href={route('dashboard')}
                            className="block rounded-lg px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100"
                        >
                            {meta.espace}
                        </Link>
                        {peutGererComptes && (
                            <Link
                                href={route('utilisateurs.index')}
                                className="block rounded-lg px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100"
                            >
                                Comptes
                            </Link>
                        )}
                        <div className="mt-2 border-t border-slate-200 pt-2">
                            <div className="px-3 py-1 text-sm font-bold">
                                {user.name}
                            </div>
                            <div className="px-3 text-xs text-slate-500">
                                {user.email} · {meta.label}
                            </div>
                            <Link
                                href={route('profile.edit')}
                                className="mt-1 block rounded-lg px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100"
                            >
                                Mon profil
                            </Link>
                            <Link
                                href={route('logout')}
                                method="post"
                                as="button"
                                className="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm font-semibold text-[#E23744] hover:bg-red-50"
                            >
                                <LogOut className="h-4 w-4" />
                                Se déconnecter
                            </Link>
                        </div>
                    </div>
                )}
            </nav>

            {header && (
                <header className="border-b border-slate-200 bg-white">
                    <div className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                        {header}
                    </div>
                </header>
            )}

            <main className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                {children}
            </main>
        </div>
    );
}
