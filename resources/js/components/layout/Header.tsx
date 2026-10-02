import { Link, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { Menu, X } from 'lucide-react';
import type { SharedProps } from '@/types';

interface HeaderProps {
    nav: SharedProps['nav'];
    brand: SharedProps['brand'];
    auth: SharedProps['auth'];
}

/**
 * Sticky site header with a mobile drawer. The drawer closes on navigation,
 * locks background scroll while open, and dismisses on Escape.
 */
export default function Header({ nav, brand, auth }: HeaderProps) {
    const [open, setOpen] = useState(false);
    const { url } = usePage<{ url: string }>().props;

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
        `rounded-full px-4 py-2 text-sm font-medium transition-colors ${
            isActive(href) ? 'bg-pickle-50 text-pickle-800' : 'text-slate hover:bg-pickle-50 hover:text-pickle-800'
        }`;

    return (
        <header className="border-hairline bg-white/90 sticky top-0 z-40 border-b backdrop-blur">
            <div className="mx-auto flex w-full max-w-7xl items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">
                <Link href="/" className="flex shrink-0 items-center gap-2" aria-label={`${brand.name} home`}>
                    <span className="bg-pickle-500 grid h-9 w-9 place-items-center rounded-xl text-lg font-extrabold text-white">
                        P
                    </span>
                    <span className="font-display text-xl font-extrabold text-pickle-900">{brand.name}</span>
                </Link>

                <nav className="hidden items-center gap-1 lg:flex" aria-label="Main">
                    {nav.slice(0, 5).map((item) => (
                        <Link
                            key={item.href}
                            href={item.href}
                            className={linkClass(item.href)}
                            aria-current={isActive(item.href) ? 'page' : undefined}
                        >
                            {item.label}
                        </Link>
                    ))}
                </nav>

                <div className="flex items-center gap-2">
                    <Link
                        href={auth.user ? '/dashboard' : '/login'}
                        className="border-hairline text-pickle-800 hover:bg-pickle-50 hidden rounded-card border bg-white px-5 py-2.5 text-sm font-semibold sm:inline-block"
                    >
                        {auth.user ? 'Dashboard' : 'Log in'}
                    </Link>

                    <Link
                        href="/book"
                        className="bg-energetic-500 hover:bg-energetic-600 hidden rounded-card px-5 py-2.5 text-sm font-semibold text-white transition-colors sm:inline-block"
                    >
                        Book a Court
                    </Link>

                    <button
                        type="button"
                        onClick={() => setOpen((value) => !value)}
                        className="border-hairline text-pickle-900 grid h-10 w-10 place-items-center rounded-card border bg-white lg:hidden"
                        aria-expanded={open}
                        aria-controls="mobile-menu"
                        aria-label={open ? 'Close menu' : 'Open menu'}
                    >
                        {open ? <X size={20} /> : <Menu size={20} />}
                    </button>
                </div>
            </div>

            {open && (
                <div id="mobile-menu" className="border-hairline border-t bg-white lg:hidden">
                    <nav className="mx-auto flex w-full max-w-7xl flex-col px-4 py-4 sm:px-6" aria-label="Mobile">
                        {nav.map((item) => (
                            <Link
                                key={item.href}
                                href={item.href}
                                className={`rounded-card px-4 py-3 text-base font-medium ${
                                    isActive(item.href) ? 'bg-pickle-50 text-pickle-800' : 'text-slate'
                                }`}
                                aria-current={isActive(item.href) ? 'page' : undefined}
                            >
                                {item.label}
                            </Link>
                        ))}

                        <div className="mt-3 flex flex-col gap-2 border-t border-hairline pt-4">
                            <Link
                                href={auth.user ? '/dashboard' : '/login'}
                                className="border-hairline text-pickle-800 rounded-card border px-4 py-3 text-center font-semibold"
                            >
                                {auth.user ? 'Dashboard' : 'Log in'}
                            </Link>
                            <Link
                                href="/book"
                                className="bg-energetic-500 rounded-card px-4 py-3 text-center font-semibold text-white"
                            >
                                Book a Court
                            </Link>
                        </div>
                    </nav>
                </div>
            )}
        </header>
    );
}
