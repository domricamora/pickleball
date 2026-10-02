import { Link } from '@inertiajs/react';
import { Mail, Phone } from 'lucide-react';
import type { SharedProps } from '@/types';

/**
 * lucide-react 1.x removed brand icons, so social marks are small inline SVGs
 * rather than icon-set imports.
 */
function FacebookMark() {
    return (
        <svg viewBox="0 0 24 24" width="17" height="17" fill="currentColor" aria-hidden focusable="false">
            <path d="M22 12a10 10 0 1 0-11.56 9.88v-6.99H7.9V12h2.54V9.8c0-2.5 1.49-3.89 3.77-3.89 1.1 0 2.24.2 2.24.2v2.46h-1.26c-1.24 0-1.63.77-1.63 1.56V12h2.78l-.45 2.89h-2.33v6.99A10 10 0 0 0 22 12Z" />
        </svg>
    );
}

function InstagramMark() {
    return (
        <svg
            viewBox="0 0 24 24"
            width="17"
            height="17"
            fill="none"
            stroke="currentColor"
            strokeWidth="2"
            strokeLinecap="round"
            strokeLinejoin="round"
            aria-hidden
            focusable="false"
        >
            <rect x="2" y="2" width="20" height="20" rx="5" ry="5" />
            <circle cx="12" cy="12" r="4" />
            <circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none" />
        </svg>
    );
}

interface FooterProps {
    brand: SharedProps['brand'];
    nav: SharedProps['nav'];
    coverage: SharedProps['coverage'];
    social: SharedProps['social'];
    contact: SharedProps['contact'];
}

const resourceLinks = [
    { label: 'FAQ', href: '/faq' },
    { label: 'Blog', href: '/blog' },
    { label: 'Register', href: '/register' },
];

const legalLinks = [
    { label: 'Privacy Policy', href: '/privacy' },
    { label: 'Terms of Service', href: '/terms' },
];

export default function Footer({ brand, nav, coverage, social, contact }: FooterProps) {
    const year = new Date().getFullYear();

    return (
        <footer className="bg-pickle-900 mt-20 text-pickle-100">
            <div className="mx-auto w-full max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
                <div className="grid gap-10 md:grid-cols-2 lg:grid-cols-4">
                    <BrandColumn brand={brand} />

                    <nav aria-labelledby="footer-explore">
                        <h2 id="footer-explore" className="font-semibold text-white">
                            Explore
                        </h2>
                        <ul className="mt-4 space-y-2 text-sm">
                            {nav.map((item) => (
                                <li key={item.href}>
                                    <Link href={item.href} className="hover:text-lime-accent transition-colors">
                                        {item.label}
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </nav>

                    <nav aria-labelledby="footer-resources">
                        <h2 id="footer-resources" className="font-semibold text-white">
                            Resources
                        </h2>
                        <ul className="mt-4 space-y-2 text-sm">
                            {resourceLinks.map((item) => (
                                <li key={item.href}>
                                    <Link href={item.href} className="hover:text-lime-accent transition-colors">
                                        {item.label}
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </nav>

                    <div>
                        <h2 className="font-semibold text-white">Get in touch</h2>
                        <ContactLinks contact={contact} />
                        <SocialLinks social={social} />
                    </div>
                </div>

                <FooterBottom brand={brand} coverage={coverage} year={year} />
            </div>
        </footer>
    );
}

function BrandColumn({ brand }: { brand: SharedProps['brand'] }) {
    return (
        <div>
            <div className="flex items-center gap-2">
                <span className="bg-lime-accent grid h-9 w-9 place-items-center rounded-xl text-lg font-extrabold text-pickle-900">
                    P
                </span>
                <span className="font-display text-xl font-extrabold text-white">{brand.name}</span>
            </div>
            <p className="mt-4 text-sm text-pickle-200">{brand.description}</p>
            <p className="text-lime-accent mt-4 text-xs font-bold tracking-widest uppercase">{brand.tagline}</p>
        </div>
    );
}

function ContactLinks({ contact }: { contact: SharedProps['contact'] }) {
    return (
        <ul className="mt-4 space-y-3 text-sm">
            <li>
                <a
                    href={`mailto:${contact.email}`}
                    className="hover:text-lime-accent flex items-center gap-2 transition-colors"
                >
                    <Mail size={16} aria-hidden />
                    {contact.email}
                </a>
            </li>
            <li>
                <a
                    href={`tel:${contact.phone.replace(/\s/g, '')}`}
                    className="hover:text-lime-accent flex items-center gap-2 transition-colors"
                >
                    <Phone size={16} aria-hidden />
                    {contact.phone}
                </a>
            </li>
        </ul>
    );
}

function SocialLinks({ social }: { social: SharedProps['social'] }) {
    const links = [
        { href: social.facebook, label: 'Facebook', Icon: FacebookMark },
        { href: social.instagram, label: 'Instagram', Icon: InstagramMark },
    ].filter((link) => Boolean(link.href));

    if (links.length === 0) return null;

    return (
        <div className="mt-5 flex gap-3">
            {links.map(({ href, label, Icon }) => (
                <a
                    key={label}
                    href={href as string}
                    target="_blank"
                    rel="noopener noreferrer"
                    aria-label={label}
                    className="hover:bg-pickle-700 grid h-9 w-9 place-items-center rounded-card bg-pickle-800 transition-colors"
                >
                    <Icon />
                </a>
            ))}
        </div>
    );
}

function FooterBottom({
    brand,
    coverage,
    year,
}: {
    brand: SharedProps['brand'];
    coverage: SharedProps['coverage'];
    year: number;
}) {
    return (
        <div className="border-pickle-800 mt-12 border-t pt-8">
            <p className="text-pickle-300 text-sm">
                <strong className="text-pickle-100 font-semibold">Now serving:</strong> {coverage.join(' · ')}
            </p>
            <p className="text-pickle-400 mt-4 text-xs">
                © {year} {brand.name}. All rights reserved. Prices in Philippine pesos and include applicable taxes as
                configured by each facility.
            </p>
            <ul className="text-pickle-300 mt-3 flex flex-wrap gap-5 text-xs">
                {legalLinks.map((item) => (
                    <li key={item.href}>
                        <Link href={item.href} className="hover:text-lime-accent transition-colors">
                            {item.label}
                        </Link>
                    </li>
                ))}
            </ul>
        </div>
    );
}
