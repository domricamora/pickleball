import { Link, usePage } from '@inertiajs/react';
import PageLayout, { Section } from '@/layouts/PageLayout';
import Seo from '@/components/seo/Seo';
import type { SharedProps } from '@/types';
import { withBasePath } from '@/lib/format';

interface Tenant {
    id: number;
    name: string;
    slug: string;
    status: string;
    address_city: string | null;
    branches_count?: number;
}

interface DashboardProps {
    role: string | null;
    isPlatformStaff: boolean;
    organization: { id: number; name: string; slug: string; status: string } | null;
    branch: { id: number; name: string; address_city: string | null } | null;
    stats: { branches: number; staff: number };
    tenants: Tenant[];
}

export default function Dashboard({ role, isPlatformStaff, organization, branch, stats, tenants }: DashboardProps) {
    const shared = usePage<SharedProps>().props;

    const tiles = [
        { label: 'Branches in scope', value: stats.branches },
        { label: 'Staff accounts', value: stats.staff },
        { label: 'Your role', value: role ?? 'None' },
        { label: 'Tenant', value: organization?.name ?? (isPlatformStaff ? 'All tenants' : '—') },
    ];

    return (
        <PageLayout shared={shared}>
            <Seo title="Dashboard" description="Your PicklePlay dashboard." noindex />

            <Section className="py-14">
                <h1 className="font-display text-mist-50 text-3xl sm:text-4xl">Dashboard</h1>
                <p className="text-mist-400 mt-3 leading-relaxed">
                    Signed in as {shared.auth.user?.name}.{' '}
                    {isPlatformStaff
                        ? 'You have platform-wide access to every tenant.'
                        : 'You are seeing data for your facility only.'}
                </p>

                <div className="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {tiles.map((tile) => (
                        <div key={tile.label} className="bg-night-850 border-hairline-dark rounded-card border p-6">
                            <p className="text-mist-400 text-sm">{tile.label}</p>
                            <p className="font-display-plain text-lime-accent mt-1 truncate text-2xl">{tile.value}</p>
                        </div>
                    ))}
                </div>

                {branch && (
                    <p className="text-mist-400 mt-8 text-sm">
                        Active branch: <span className="text-mist-50 font-semibold">{branch.name}</span>
                        {branch.address_city ? ` — ${branch.address_city}` : ''}
                    </p>
                )}
            </Section>

            <Section className="pb-14">
                <h2 className="font-display text-mist-50 text-2xl sm:text-3xl">Tenants</h2>

                {tenants.length === 0 ? (
                    <div className="border-hairline-dark bg-night-850 mt-6 rounded-card border border-dashed px-6 py-12 text-center">
                        <p className="text-mist-400">No tenants yet.</p>
                    </div>
                ) : (
                    <ul className="mt-6 space-y-3">
                        {tenants.map((tenant) => (
                            <li
                                key={tenant.id}
                                className="border-hairline-dark bg-night-850 flex items-center justify-between gap-4 rounded-card border px-5 py-4"
                            >
                                <div>
                                    <p className="font-display-plain text-mist-50">{tenant.name}</p>
                                    <p className="text-mist-400 text-sm">
                                        {tenant.address_city ?? 'No city set'} · {tenant.branches_count ?? 0} branch
                                        {(tenant.branches_count ?? 0) === 1 ? '' : 'es'}
                                    </p>
                                </div>
                                <span
                                    className={`rounded-pill px-3 py-1 text-xs font-bold uppercase ${
                                        tenant.status === 'active'
                                            ? 'bg-night-800 text-lime-accent'
                                            : 'bg-energetic-500/15 text-energetic-300'
                                    }`}
                                >
                                    {tenant.status}
                                </span>
                            </li>
                        ))}
                    </ul>
                )}
            </Section>

            <Section className="pb-16">
                <h2 className="font-display text-mist-50 text-2xl sm:text-3xl">Next up</h2>
                <div className="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <PhaseCard
                        title="Facility & Court Management"
                        body="Add your courts, set pricing and define opening hours."
                        href="/facilities"
                    />
                    <PhaseCard
                        title="Booking Engine"
                        body="Live availability with double-booking protection."
                        href="/book"
                    />
                    <PhaseCard title="Your profile" body="Update your name, email and playing level." href="/profile" />
                </div>
            </Section>
        </PageLayout>
    );
}

function PhaseCard({ title, body, href }: { title: string; body: string; href: string }) {
    return (
        <article className="bg-night-850 border-hairline-dark hover:border-night-600 rounded-card border p-6 transition-colors">
            <h3 className="font-display-plain text-mist-50 tracking-wide uppercase">{title}</h3>
            <p className="text-mist-400 mt-2 text-sm leading-relaxed">{body}</p>
            <Link
                href={withBasePath(href)}
                className="text-lime-accent hover:text-lime-accent-dark mt-4 inline-block text-sm font-semibold transition-colors"
            >
                Open
            </Link>
        </article>
    );
}
