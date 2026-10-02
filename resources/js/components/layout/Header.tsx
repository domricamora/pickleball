import { Link, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { Menu, X } from 'lucide-react';
import type { SharedProps } from '@/types';
import { withBasePath } from '@/lib/format';

interface HeaderProps {
    nav: SharedProps['nav'];
    brand: SharedProps['brand'];
    auth: SharedProps['auth'];
}

/**
 * Sticky site header with a mobile drawer. The drawer closes on navigation,
 * locks background scroll while open, and dismisses on Escape.
 *
 * The bar is transparent over the home hero and solid everywhere else, so
 * inner pages do not show content sliding under a see-through bar.
 */
export default function Header({ nav, brand, auth }: HeaderProps) {
    const [open, setOpen] = useState(false);
    // Inertia v3 exposes the current URL at the top level of the page object,
    // not inside props. Reading it from props yields undefined and breaks
    // isActive() on the first render.
    const { url } = usePage();

    // Only the home page opens with a full-bleed photograph behind the header.
    const overHero = url === '/';

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

    const isActive = (href: string) => url === href || url.startsWith(`${href}/`);
    const linkClass = (href: string) =>
        `rounded-pill px-4 py-2 text-sm font-medium transition-colors ${
            isActive(href) ? 'bg-night-800 text-mist-50' : 'text-mist-300 hover:bg-night-800/70 hover:text-mist-50'
        }`;

    return (
        <header
            className={`sticky top-0 z-40 border-b backdrop-blur ${
                overHero ? 'border-transparent bg-night-950/70' : 'border-hairline-dark bg-night-950/95'
            }`}
        >
            <div className="mx-auto flex w-full max-w-7xl items-center justify-between gap-4 px-4 py-3.5 sm:px-6 lg:px-8">
                <Link
                    href={withBasePath('/')}
                    className="flex shrink-0 items-center gap-2"
                    aria-label={`${brand.name} home`}
                >
                    <span className="bg-lime-accent grid h-8 w-8 place-items-center rounded-pill font-display-plain text-night-950">
                        P
                    </span>
                    <span className="font-display-plain text-mist-50 text-lg">{brand.name}</span>
                </Link>

                <nav className="hidden items-center gap-1 lg:flex" aria-label="Main">
                    {nav.slice(0, 6).map((item) => (
                        <Link
                            key={item.href}
                            href={withBasePath(item.href)}
                            className={linkClass(item.href)}
                            aria-current={isActive(item.href) ? 'page' : undefined}
                        >
                            {item.label}
                        </Link>
                    ))}
                </nav>

                <div className="flex items-center gap-2">
                    <Link
                        href={withBasePath(auth.user ? '/dashboard' : '/login')}
                        className="text-mist-200 hover:bg-night-800 hidden rounded-pill px-4 py-2.5 text-sm font-medium transition-colors sm:inline-block"
                    >
                        {auth.user ? 'Dashboard' : 'Log in'}
                    </Link>

                    <Link
                        href={withBasePath('/book')}
                        className="btn-pill btn-pill-primary hidden px-5 py-2.5 text-xs sm:inline-flex"
                    >
                        Book a court
                    </Link>

                    <button
                        type="button"
                        onClick={() => setOpen((value) => !value)}
                        className="border-hairline-dark text-mist-50 grid h-10 w-10 place-items-center rounded-pill border lg:hidden"
                        aria-expanded={open}
                        aria-controls="mobile-menu"
                        aria-label={open ? 'Close menu' : 'Open menu'}
                    >
                        {open ? <X size={20} /> : <Menu size={20} />}
                    </button>
                </div>
            </div>

            {open && (
                <div id="mobile-menu" className="border-hairline-dark border-t bg-night-950 lg:hidden">
                    <nav className="mx-auto flex w-full max-w-7xl flex-col px-4 py-4 sm:px-6" aria-label="Mobile">
                        {nav.map((item) => (
                            <Link
                                key={item.href}
                                href={withBasePath(item.href)}
                                className={`rounded-pill px-4 py-3 text-base font-medium ${
                                    isActive(item.href) ? 'bg-night-800 text-mist-50' : 'text-mist-300'
                                }`}
                                aria-current={isActive(item.href) ? 'page' : undefined}
                            >
                                {item.label}
                            </Link>
                        ))}

                        <div className="border-hairline-dark mt-3 flex flex-col gap-2 border-t pt-4">
                            <Link
                                href={withBasePath(auth.user ? '/dashboard' : '/login')}
                                className="border-hairline-dark text-mist-100 rounded-pill border px-4 py-3 text-center font-semibold"
                            >
                                {auth.user ? 'Dashboard' : 'Log in'}
                            </Link>
                            <Link href={withBasePath('/book')} className="btn-pill btn-pill-primary px-4 py-3">
                                Book a court
                            </Link>
                        </div>
                    </nav>
                </div>
            )}
        </header>
    );
}
