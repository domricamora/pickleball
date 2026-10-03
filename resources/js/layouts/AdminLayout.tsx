import { Link, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import type { ComponentType, ReactNode } from 'react';
import { CalendarDays, LayoutDashboard, LayoutGrid, MapPinned, Menu, X } from 'lucide-react';
import type { SharedProps } from '@/types';
import { withBasePath } from '@/lib/format';

interface AdminLayoutProps {
    children: ReactNode;
    title: string;
    subtitle?: string;
    actions?: ReactNode;
}

interface NavItem {
    label: string;
    href: string;
    icon: ComponentType<{ size?: number; strokeWidth?: number; 'aria-hidden'?: boolean }>;
}

/**
 * Back-office shell: a persistent left sidebar with the content beside it.
 *
 * The sidebar is a real column at lg and above -- it scrolls independently so
 * the header and content stay put. Below lg it collapses behind a toggle and
 * slides in over the page, because a horizontally scrolling strip of links (the
 * previous behaviour) reads as an unfinished responsive fallback rather than a
 * deliberate mobile menu.
 */
const nav: NavItem[] = [
    { label: 'Dashboard', href: '/admin', icon: LayoutDashboard },
    { label: 'Facilities', href: '/admin/facilities', icon: MapPinned },
    { label: 'Courts', href: '/admin/courts', icon: LayoutGrid },
    { label: 'Book a court', href: '/book', icon: CalendarDays },
];

/**
 * The sidebar, rendered twice: inline at lg and above, and inside the drawer
 * below it. Extracted so the two copies cannot drift apart.
 */
function SidebarContent({
    items,
    isActive,
    footer,
    onNavigate,
}: {
    items: NavItem[];
    isActive: (href: string) => boolean;
    footer: ReactNode;
    onNavigate?: () => void;
}) {
    return (
        <>
            <nav className="flex flex-col gap-1" aria-label="Admin">
                {items.map((item) => {
                    const Icon = item.icon;
                    const active = isActive(item.href);

                    return (
                        <Link
                            key={item.href}
                            href={withBasePath(item.href)}
                            onClick={onNavigate}
                            aria-current={active ? 'page' : undefined}
                            className={`group flex items-center gap-3 rounded-card px-3 py-2.5 text-sm font-medium transition-colors ${
                                active
                                    ? 'bg-pickle-700 text-white'
                                    : 'text-pickle-200 hover:bg-pickle-800 hover:text-white'
                            }`}
                        >
                            <Icon size={18} strokeWidth={1.75} aria-hidden={true} />
                            {item.label}
                        </Link>
                    );
                })}
            </nav>

            <div className="mt-auto">{footer}</div>
        </>
    );
}

export default function AdminLayout({ children, title, subtitle, actions }: AdminLayoutProps) {
    const shared = usePage<SharedProps>().props;
    // Inertia v3 exposes the current URL at the top level of the page object,
    // not inside props.
    const { url } = usePage();
    const [open, setOpen] = useState(false);

    const isActive = (href: string) => url === href || url.startsWith(`${href}/`);
    const canManage = shared.permissions.includes('facilities.manage') || shared.permissions.includes('courts.manage');

    // Close on navigation, lock background scroll, and dismiss on Escape --
    // the same behaviour as the public header's drawer.
    useEffect(() => {
        setOpen(false);
    }, [url]);

    useEffect(() => {
        document.body.style.overflow = open ? 'hidden' : '';

        return () => {
            document.body.style.overflow = '';
        };
    }, [open]);

    useEffect(() => {
        if (!open) return;

        const onKeyDown = (event: KeyboardEvent) => {
            if (event.key === 'Escape') setOpen(false);
        };

        window.addEventListener('keydown', onKeyDown);

        return () => window.removeEventListener('keydown', onKeyDown);
    }, [open]);

    const brand = (
        <Link
            href={withBasePath('/admin')}
            className="flex items-center gap-2.5"
            aria-label={`${shared.brand.name} admin home`}
        >
            <span className="bg-lime-accent font-display-plain text-pickle-900 grid h-9 w-9 place-items-center rounded-card text-lg font-extrabold">
                P
            </span>
            <span className="leading-tight">
                <span className="font-display-plain block text-base font-extrabold text-white">
                    {shared.brand.name}
                </span>
                <span className="text-pickle-400 block text-[0.6875rem] font-semibold tracking-widest uppercase">
                    Admin
                </span>
            </span>
        </Link>
    );

    const footer = (
        <div className="border-pickle-800 border-t pt-4">
            <p className="text-pickle-100 text-sm font-semibold">{shared.auth.user?.name}</p>
            <p className="text-pickle-400 text-xs">{shared.auth.role}</p>
            <Link
                href={withBasePath('/')}
                className="text-pickle-300 hover:text-lime-accent mt-3 inline-block text-xs transition-colors"
            >
                Back to public site
            </Link>
            {!canManage && <p className="text-pickle-500 mt-3 text-xs">Read-only access</p>}
        </div>
    );

    return (
        <div className="bg-soft-bg min-h-screen lg:flex">
            {/* Persistent left column from lg up; scrolls independently. */}
            <aside className="bg-pickle-900 hidden w-64 shrink-0 flex-col px-4 py-6 lg:sticky lg:top-0 lg:flex lg:h-screen lg:overflow-y-auto">
                {brand}

                <div className="mt-8 flex flex-1 flex-col gap-6">
                    <SidebarContent items={nav} isActive={isActive} footer={footer} />
                </div>
            </aside>

            {/* Below lg the same column slides in over the page. */}
            {open && (
                <div className="fixed inset-0 z-50 lg:hidden">
                    <button
                        type="button"
                        onClick={() => setOpen(false)}
                        className="absolute inset-0 bg-night-950/60"
                        aria-label="Close menu"
                    />

                    <div className="bg-pickle-900 relative flex h-full w-72 max-w-[85%] flex-col px-4 py-5 shadow-xl">
                        <div className="flex items-center justify-between gap-3">
                            {brand}

                            <button
                                type="button"
                                onClick={() => setOpen(false)}
                                className="text-pickle-200 hover:bg-pickle-800 hover:text-white grid h-9 w-9 shrink-0 place-items-center rounded-card transition-colors"
                                aria-label="Close menu"
                            >
                                <X size={20} aria-hidden={true} />
                            </button>
                        </div>

                        <div className="mt-7 flex flex-1 flex-col gap-6 overflow-y-auto">
                            <SidebarContent
                                items={nav}
                                isActive={isActive}
                                footer={footer}
                                onNavigate={() => setOpen(false)}
                            />
                        </div>
                    </div>
                </div>
            )}

            <div className="flex min-w-0 flex-1 flex-col">
                <header className="border-hairline bg-white sticky top-0 z-30 border-b">
                    <div className="flex items-center gap-3 px-5 py-4 sm:px-8">
                        <button
                            type="button"
                            onClick={() => setOpen(true)}
                            className="border-hairline text-pickle-900 hover:bg-soft-bg grid h-10 w-10 shrink-0 place-items-center rounded-card border transition-colors lg:hidden"
                            aria-expanded={open}
                            aria-controls="admin-menu"
                            aria-label="Open menu"
                        >
                            <Menu size={20} aria-hidden={true} />
                        </button>

                        <div className="min-w-0 flex-1">
                            <h1 className="font-display text-pickle-900 truncate text-xl font-extrabold sm:text-2xl">
                                {title}
                            </h1>
                            {subtitle && <p className="text-slate mt-0.5 truncate text-sm">{subtitle}</p>}
                        </div>

                        {actions && <div className="flex shrink-0 items-center gap-3">{actions}</div>}
                    </div>
                </header>

                <main className="flex-1 px-5 py-7 sm:px-8">{children}</main>
            </div>
        </div>
    );
}
