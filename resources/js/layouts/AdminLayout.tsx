import { Link, usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import type { SharedProps } from '@/types';
import { withBasePath } from '@/lib/format';

interface AdminLayoutProps {
    children: ReactNode;
    title: string;
    subtitle?: string;
    actions?: ReactNode;
}

const nav = [
    { label: 'Dashboard', href: '/admin' },
    { label: 'Facilities', href: '/admin/facilities' },
    { label: 'Courts', href: '/admin/courts' },
    { label: 'Book a court', href: '/book' },
];

/**
 * Back-office shell: a fixed sidebar plus a content column.
 */
export default function AdminLayout({ children, title, subtitle, actions }: AdminLayoutProps) {
    const shared = usePage<SharedProps>().props;
    // Inertia v3 exposes the current URL at the top level of the page object,
    // not inside props.
    const { url } = usePage();

    const isActive = (href: string) => url === href || url.startsWith(`${href}/`);
    const canManage = shared.permissions.includes('facilities.manage') || shared.permissions.includes('courts.manage');

    return (
        <div className="bg-pickle-900 flex min-h-screen flex-col lg:flex-row">
            <aside className="flex shrink-0 flex-col bg-pickle-900 px-4 py-6 lg:w-64 lg:px-6">
                <Link href="/admin" className="flex items-center gap-2">
                    <span className="bg-lime-accent grid h-9 w-9 place-items-center rounded-xl text-lg font-extrabold text-pickle-900">
                        P
                    </span>
                    <span className="font-display text-lg font-extrabold text-white">{shared.brand.name}</span>
                </Link>
                <p className="text-pickle-400 mt-1 text-xs font-semibold tracking-widest uppercase">Admin</p>

                <nav className="mt-8 flex gap-1 overflow-x-auto lg:flex-col" aria-label="Admin">
                    {nav.map((item) => (
                        <Link
                            key={item.href}
                            href={withBasePath(item.href)}
                            className={`shrink-0 rounded-card px-4 py-2.5 text-sm font-medium transition-colors ${
                                isActive(item.href)
                                    ? 'bg-pickle-700 text-white'
                                    : 'text-pickle-200 hover:bg-pickle-800 hover:text-white'
                            }`}
                            aria-current={isActive(item.href) ? 'page' : undefined}
                        >
                            {item.label}
                        </Link>
                    ))}
                </nav>

                <div className="mt-auto hidden pt-8 lg:block">
                    <p className="text-pickle-400 text-xs">
                        {shared.auth.user?.name}
                        <br />
                        {shared.auth.role}
                    </p>
                    <Link
                        href="/"
                        className="text-pickle-300 hover:text-lime-accent mt-3 inline-block text-xs transition-colors"
                    >
                        Back to public site
                    </Link>
                    {!canManage && <p className="text-pickle-500 mt-3 text-xs">Read-only access</p>}
                </div>
            </aside>

            <div className="flex min-w-0 flex-1 flex-col bg-soft-bg">
                <header className="border-hairline border-b bg-white">
                    <div className="flex flex-wrap items-center justify-between gap-4 px-5 py-5 sm:px-8">
                        <div>
                            <h1 className="font-display text-2xl font-extrabold text-pickle-900">{title}</h1>
                            {subtitle && <p className="text-slate mt-1 text-sm">{subtitle}</p>}
                        </div>
                        {actions && <div className="flex items-center gap-3">{actions}</div>}
                    </div>
                </header>

                <main className="flex-1 px-5 py-7 sm:px-8">{children}</main>
            </div>
        </div>
    );
}
