import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { usePage } from '@inertiajs/react';
import type { SharedProps } from '@/types';

interface AuthLayoutProps {
    children: ReactNode;
    title: string;
    subtitle?: string;
}

const footerLinks = [
    { label: 'Home', href: '/' },
    { label: 'Facilities', href: '/facilities' },
    { label: 'Pricing', href: '/pricing' },
    { label: 'About', href: '/about' },
];

/**
 * Split-screen shell for every auth screen: brand panel plus the form card.
 */
export default function AuthLayout({ children, title, subtitle }: AuthLayoutProps) {
    const shared = usePage<SharedProps>().props;

    return (
        <div className="grid min-h-screen lg:grid-cols-2">
            <div className="bg-pickle-900 text-white relative hidden overflow-hidden p-12 lg:flex lg:flex-col lg:justify-between">
                <div
                    className="pointer-events-none absolute inset-0 opacity-25"
                    style={{
                        backgroundImage:
                            'linear-gradient(to right, rgba(255,255,255,.14) 1px, transparent 1px), linear-gradient(to bottom, rgba(255,255,255,.14) 1px, transparent 1px)',
                        backgroundSize: '52px 52px',
                    }}
                    aria-hidden
                />

                <Link href="/" className="relative flex items-center gap-2">
                    <span className="bg-lime-accent grid h-10 w-10 place-items-center rounded-xl text-lg font-extrabold text-pickle-900">
                        P
                    </span>
                    <span className="font-display text-xl font-extrabold text-white">{shared.brand.name}</span>
                </Link>

                <div className="relative">
                    <h2 className="font-display text-4xl font-extrabold">{shared.brand.headline}</h2>
                    <p className="mt-4 max-w-md text-pickle-100">{shared.brand.description}</p>
                    <p className="text-lime-accent mt-6 text-sm font-bold tracking-widest uppercase">
                        {shared.brand.tagline}
                    </p>
                </div>

                <p className="text-pickle-300 relative text-sm">{shared.coverage.join(' · ')}</p>
            </div>

            <div className="flex flex-col justify-center px-5 py-10 sm:px-8">
                <div className="mx-auto w-full max-w-md">
                    <Link href="/" className="mb-8 flex items-center gap-2 lg:hidden">
                        <span className="bg-pickle-500 grid h-9 w-9 place-items-center rounded-xl text-lg font-extrabold text-white">
                            P
                        </span>
                        <span className="font-display text-lg font-extrabold text-pickle-900">{shared.brand.name}</span>
                    </Link>

                    <h1 className="font-display text-3xl font-extrabold text-pickle-900">{title}</h1>
                    {subtitle && <p className="text-slate mt-2">{subtitle}</p>}

                    <div className="mt-8">{children}</div>
                </div>

                <nav className="mx-auto mt-10 w-full max-w-md text-center text-sm text-slate" aria-label="Footer">
                    {footerLinks.map((link) => (
                        <Link key={link.href} href={link.href} className="hover:text-pickle-700 mx-2 transition-colors">
                            {link.label}
                        </Link>
                    ))}
                </nav>
            </div>
        </div>
    );
}
