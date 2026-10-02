import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/AppLayout';
import { ButtonLink } from '@/components/ui/Button';

interface Stat {
    label: string;
    value: string;
}

interface WelcomeProps {
    headline: string;
    tagline: string;
    stats: Stat[];
}

const pillars = [
    { title: 'Court Bookings', body: 'Real-time availability with double-booking protection.' },
    { title: 'POS & Products', body: 'Paddles, balls, apparel and rentals from one register.' },
    { title: 'Memberships', body: 'Plans, packages, usage tracking and expiry.' },
    { title: 'Reports', body: 'Revenue, occupancy and court utilization.' },
];

export default function Welcome({ headline, tagline, stats }: WelcomeProps) {
    return (
        <AppLayout title="Home">
            <Head>
                <meta
                    name="description"
                    content="Book pickleball courts, join games, manage memberships and stay connected with your local pickleball community."
                />
            </Head>

            {/* Hero — plan.md §4 */}
            <section className="bg-pickle-900 text-white rounded-panel relative overflow-hidden px-6 py-16 sm:px-12 sm:py-24">
                <div
                    className="pointer-events-none absolute inset-0 opacity-25"
                    style={{
                        backgroundImage:
                            'linear-gradient(to right, rgba(255,255,255,.14) 1px, transparent 1px), linear-gradient(to bottom, rgba(255,255,255,.14) 1px, transparent 1px)',
                        backgroundSize: '48px 48px',
                    }}
                />
                <div className="relative">
                    <span className="bg-lime-accent text-pickle-900 rounded-full px-4 py-1.5 text-xs font-bold tracking-wide uppercase">
                        Philippines
                    </span>
                    <h1 className="font-display mt-6 max-w-3xl text-4xl font-extrabold sm:text-6xl">{headline}</h1>
                    <p className="mt-6 max-w-2xl text-lg text-pickle-100">
                        Book pickleball courts, join games, manage memberships, buy products and stay connected with
                        your local pickleball community.
                    </p>
                    <p className="text-lime-accent mt-4 font-semibold tracking-widest uppercase">{tagline}</p>

                    <div className="mt-10 flex flex-col gap-3 sm:flex-row">
                        <ButtonLink href="#book" variant="accent">
                            Book a Court
                        </ButtonLink>
                        <ButtonLink href="#facilities" variant="ghost">
                            Explore Facilities
                        </ButtonLink>
                    </div>
                </div>
            </section>

            {/* Foundation checklist */}
            <section className="mt-14">
                <h2 className="font-display text-2xl font-bold text-pickle-900 sm:text-3xl">Foundation ready</h2>
                <p className="text-slate mt-2">
                    Phase 0 wires the stack this platform grows on: Laravel, Inertia, React, Tailwind and MySQL.
                </p>

                <div className="mt-6 grid gap-4 sm:grid-cols-3">
                    {stats.map((stat) => (
                        <div key={stat.label} className="border-hairline rounded-card border bg-white p-6 shadow-sm">
                            <p className="font-display text-3xl font-extrabold text-pickle-600">{stat.value}</p>
                            <p className="text-slate mt-1 text-sm font-medium">{stat.label}</p>
                        </div>
                    ))}
                </div>
            </section>

            {/* Domain preview */}
            <section className="mt-14">
                <h2 className="font-display text-2xl font-bold text-pickle-900 sm:text-3xl">Platform modules</h2>
                <div className="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {pillars.map((pillar) => (
                        <article key={pillar.title} className="border-hairline rounded-card border bg-white p-6">
                            <h3 className="font-semibold text-pickle-800">{pillar.title}</h3>
                            <p className="text-slate mt-2 text-sm">{pillar.body}</p>
                        </article>
                    ))}
                </div>
            </section>
        </AppLayout>
    );
}
