import { Link, usePage } from '@inertiajs/react';
import PageLayout, { Section } from '@/layouts/PageLayout';
import Seo from '@/components/seo/Seo';
import type { SharedProps } from '@/types';

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

            <Section className="py-10">
                <h1 className="font-display text-3xl font-extrabold text-pickle-900 sm:text-4xl">Dashboard</h1>
                <p className="text-slate mt-2">
                    Signed in as {shared.auth.user?.name}.{' '}
                    {isPlatformStaff
                        ? 'You have platform-wide access to every tenant.'
                        : 'You are seeing data for your facility only.'}
                </p>

                <div className="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {tiles.map((tile) => (
                        <div key={tile.label} className="border-hairline rounded-card border bg-white p-6">
                            <p className="text-slate text-sm font-medium">{tile.label}</p>
                            <p className="font-display mt-1 truncate text-2xl font-extrabold text-pickle-700">
                                {tile.value}
                            </p>
                        </div>
                    ))}
                </div>

                {branch && (
                    <p className="text-slate mt-6 text-sm">
                        Active branch: <span className="text-pickle-800 font-semibold">{branch.name}</span>
                        {branch.address_city ? ` — ${branch.address_city}` : ''}
                    </p>
                )}
            </Section>

            <Section className="pb-12">
                <h2 className="font-display text-2xl font-extrabold text-pickle-900">Tenants</h2>

                {tenants.length === 0 ? (
                    <div className="border-hairline mt-4 rounded-card border border-dashed bg-white px-6 py-12 text-center">
                        <p className="text-slate">No tenants yet.</p>
                    </div>
                ) : (
                    <ul className="mt-4 space-y-3">
                        {tenants.map((tenant) => (
                            <li
                                key={tenant.id}
                                className="border-hairline flex items-center justify-between gap-4 rounded-card border bg-white px-5 py-4"
                            >
                                <div>
                                    <p className="text-pickle-900 font-semibold">{tenant.name}</p>
                                    <p className="text-slate text-sm">
                                        {tenant.address_city ?? 'No city set'} · {tenant.branches_count ?? 0} branch
                                        {(tenant.branches_count ?? 0) === 1 ? '' : 'es'}
                                    </p>
                                </div>
                                <span
                                    className={`rounded-full px-3 py-1 text-xs font-bold uppercase ${
                                        tenant.status === 'active'
                                            ? 'bg-pickle-50 text-pickle-700'
                                            : 'bg-energetic-50 text-energetic-700'
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
                <h2 className="font-display text-2xl font-extrabold text-pickle-900">Next up</h2>
                <div className="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
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
        <article className="border-hairline rounded-card border bg-white p-6">
            <h3 className="text-pickle-800 font-semibold">{title}</h3>
            <p className="text-slate mt-2 text-sm">{body}</p>
            <Link href={href} className="text-pickle-700 hover:text-pickle-900 mt-4 inline-block text-sm font-semibold">
                Open
            </Link>
        </article>
    );
}
