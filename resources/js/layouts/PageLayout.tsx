import type { ReactNode } from 'react';
import { Link } from '@inertiajs/react';
import Header from '@/components/layout/Header';
import Footer from '@/components/layout/Footer';
import type { SharedProps } from '@/types';
import { withBasePath, assetUrl } from '@/lib/format';

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
        <div className="bg-night-950 text-mist-300 flex min-h-screen flex-col">
            <a
                href="#main"
                className="bg-lime-accent text-night-950 sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded-pill focus:px-4 focus:py-2 focus:font-semibold"
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
                    className="border-lime-accent bg-night-850 text-mist-50 rounded-card border-l-4 px-4 py-3 text-sm"
                >
                    {success}
                </div>
            )}
            {error && (
                <div
                    role="alert"
                    className="border-energetic-400 bg-night-850 text-mist-50 rounded-card border-l-4 px-4 py-3 text-sm"
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

interface PageHeroProps {
    eyebrow?: string;
    title: string;
    description?: string;
    /** Optional background photograph from the credited set. */
    image?: string;
    alt?: string;
    children?: ReactNode;
}

/**
 * The banner at the top of every inner marketing page.
 *
 * Mirrors the home hero's typography so the site reads as one design, but
 * without the booking panel — that panel belongs to the home page only.
 *
 * The photograph is credited in resources/media/media-credits.md and treated
 * by `.cinema`: a slow drift, a directional scrim, a vignette and a film
 * grain, all in CSS so nothing extra has to load before the effect lands.
 */
export function PageHero({ eyebrow, title, description, image, alt, children }: PageHeroProps) {
    return (
        <section className="cinema cinema-grain bg-night-950 relative isolate flex min-h-[22rem] items-end overflow-hidden border-b border-hairline-dark sm:min-h-[26rem]">
            {image && (
                <img
                    src={assetUrl(image)}
                    alt={alt ?? ''}
                    width={2000}
                    height={1125}
                    fetchPriority="high"
                    decoding="async"
                />
            )}

            <div className="mx-auto w-full max-w-7xl px-4 py-16 sm:px-6 sm:py-20 lg:px-8 lg:py-24">
                {eyebrow && <p className="eyebrow text-lime-accent">{eyebrow}</p>}
                <h1 className="font-display text-mist-50 mt-5 text-4xl sm:text-5xl lg:text-6xl">{title}</h1>
                {description && <p className="text-mist-300 mt-6 max-w-2xl text-lg leading-relaxed">{description}</p>}
                {children && <div className="mt-8">{children}</div>}
            </div>
        </section>
    );
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
        <div className="border-hairline-dark bg-night-850 rounded-panel border border-dashed px-6 py-16 text-center">
            <h3 className="font-display-plain text-mist-50 text-2xl">{title}</h3>
            <p className="text-mist-400 mx-auto mt-3 max-w-lg leading-relaxed">{description}</p>
            {actionHref && actionLabel && (
                <Link
                    href={withBasePath(actionHref)}
                    className="btn-pill btn-pill-primary mt-8 px-7 py-3 text-[0.8125rem]"
                >
                    {actionLabel}
                </Link>
            )}
        </div>
    );
}
