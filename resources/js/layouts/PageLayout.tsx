import type { ReactNode } from 'react';
import { Link } from '@inertiajs/react';
import Header from '@/components/layout/Header';
import Footer from '@/components/layout/Footer';
import type { SharedProps } from '@/types';

interface PageLayoutProps {
    children: ReactNode;
    shared: SharedProps;
}

/**
 * Public site shell: sticky header, main content, footer. Shared Inertia props
 * are supplied by HandleInertiaRequests, so no page fetches brand data itself.
 */
export default function PageLayout({ children, shared }: PageLayoutProps) {
    return (
        <div className="flex min-h-screen flex-col">
            <a
                href="#main"
                className="bg-pickle-500 sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded-card focus:px-4 focus:py-2 focus:font-semibold focus:text-white"
            >
                Skip to content
            </a>

            <Header nav={shared.nav} brand={shared.brand} auth={shared.auth} />

            <FlashMessages flash={shared.flash} />

            <main id="main" className="flex-1">
                {children}
            </main>

            <Footer
                brand={shared.brand}
                nav={shared.nav}
                coverage={shared.coverage}
                social={shared.social}
                contact={shared.contact}
            />
        </div>
    );
}

function FlashMessages({ flash }: { flash: SharedProps['flash'] }) {
    const { success, error } = flash;

    if (!success && !error) return null;

    return (
        <div className="mx-auto w-full max-w-7xl px-4 pt-4 sm:px-6 lg:px-8">
            {success && (
                <div
                    role="status"
                    className="border-pickle-200 bg-pickle-50 text-pickle-800 rounded-card border px-4 py-3 text-sm"
                >
                    {success}
                </div>
            )}
            {error && (
                <div
                    role="alert"
                    className="border-energetic-200 bg-energetic-50 text-energetic-800 rounded-card border px-4 py-3 text-sm"
                >
                    {error}
                </div>
            )}
        </div>
    );
}

/** Shared inner wrapper so every marketing page gets consistent padding. */
export function Section({ children, className = '' }: { children: ReactNode; className?: string }) {
    return <section className={`mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8 ${className}`}>{children}</section>;
}

/** Simple 404 / empty-state block used by list pages with no data yet. */
export function EmptyState({
    title,
    description,
    actionHref,
    actionLabel,
}: {
    title: string;
    description: string;
    actionHref?: string;
    actionLabel?: string;
}) {
    return (
        <div className="border-hairline rounded-panel border border-dashed bg-white px-6 py-16 text-center">
            <h3 className="font-display text-2xl font-bold text-pickle-900">{title}</h3>
            <p className="text-slate mx-auto mt-3 max-w-lg">{description}</p>
            {actionHref && actionLabel && (
                <Link
                    href={actionHref}
                    className="bg-energetic-500 hover:bg-energetic-600 mt-6 inline-block rounded-card px-6 py-3 font-semibold text-white transition-colors"
                >
                    {actionLabel}
                </Link>
            )}
        </div>
    );
}
