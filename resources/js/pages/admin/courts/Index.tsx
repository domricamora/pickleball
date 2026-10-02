import { Link, router } from '@inertiajs/react';
import { AdminButton, AdminPage, StatusBadge } from '@/components/admin/AdminUi';

interface Court {
    id: number;
    name: string;
    number: string | null;
    status: { value: string; label: string; badgeClass: string };
    surface: string;
    setting: string;
    capacity: number;
    branch: { id: number; name: string };
}

interface Branch {
    id: number;
    name: string;
}

interface IndexProps {
    courts: Court[];
    branches: Branch[];
    filters: { branch_id: number | null; status: string | null };
}

export default function Index({ courts, branches, filters }: IndexProps) {
    const applyFilter = (key: 'branch_id' | 'status', value: string) => {
        router.get(
            '/admin/courts',
            {
                branch_id: filters.branch_id ?? undefined,
                status: filters.status ?? undefined,
                [key]: value || undefined,
            },
            { preserveState: true, replace: true },
        );
    };

    return (
        <AdminPage
            title="Courts"
            subtitle="Every court in your facility, with its surface and current status."
            actions={
                <AdminButton href="/admin/courts/create" variant="primary">
                    Add court
                </AdminButton>
            }
        >
            <div className="border-hairline rounded-card mb-5 flex flex-wrap items-center gap-3 border bg-white px-4 py-3">
                <label htmlFor="filter-branch" className="text-pickle-900 text-sm font-medium">
                    Facility
                </label>
                <select
                    id="filter-branch"
                    value={filters.branch_id ?? ''}
                    onChange={(e) => applyFilter('branch_id', e.target.value)}
                    className="border-hairline focus:border-pickle-500 rounded-card border px-3 py-2 text-sm"
                >
                    <option value="">All facilities</option>
                    {branches.map((branch) => (
                        <option key={branch.id} value={branch.id}>
                            {branch.name}
                        </option>
                    ))}
                </select>
            </div>

            {courts.length === 0 ? <EmptyCourts /> : <CourtTable courts={courts} />}
        </AdminPage>
    );
}

function EmptyCourts() {
    return (
        <div className="border-hairline rounded-panel border border-dashed bg-white px-6 py-16 text-center">
            <h2 className="font-display text-2xl font-bold text-pickle-900">No courts yet</h2>
            <p className="text-slate mx-auto mt-2 max-w-md">
                Add your first court to start taking bookings. You can set its surface, capacity and opening hours next.
            </p>
            <Link
                href="/admin/courts/create"
                className="bg-energetic-500 hover:bg-energetic-600 mt-6 inline-block rounded-card px-6 py-3 font-semibold text-white transition-colors"
            >
                Add court
            </Link>
        </div>
    );
}

function CourtTable({ courts }: { courts: Court[] }) {
    return (
        <div className="border-hairline overflow-x-auto rounded-panel border bg-white">
            <table className="w-full text-left text-sm">
                <caption className="sr-only">Courts in your organisation</caption>
                <thead className="border-hairline bg-pickle-50/60 border-b">
                    <tr>
                        <th scope="col" className="px-5 py-3 font-semibold text-pickle-900">
                            Court
                        </th>
                        <th scope="col" className="px-5 py-3 font-semibold text-pickle-900">
                            Facility
                        </th>
                        <th scope="col" className="px-5 py-3 font-semibold text-pickle-900">
                            Surface
                        </th>
                        <th scope="col" className="px-5 py-3 font-semibold text-pickle-900">
                            Capacity
                        </th>
                        <th scope="col" className="px-5 py-3 font-semibold text-pickle-900">
                            Status
                        </th>
                        <th scope="col" className="px-5 py-3 text-right font-semibold text-pickle-900">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody className="divide-hairline divide-y">
                    {courts.map((court) => (
                        <tr key={court.id}>
                            <td className="px-5 py-4">
                                <Link
                                    href={`/admin/courts/${court.id}`}
                                    className="text-pickle-800 font-semibold hover:underline"
                                >
                                    {court.name}
                                </Link>
                                {court.number && <span className="text-slate ml-2 text-xs">#{court.number}</span>}
                            </td>
                            <td className="text-slate px-5 py-4">{court.branch?.name}</td>
                            <td className="text-slate px-5 py-4 capitalize">
                                {String(court.surface).replace('_', ' ')} · {court.setting}
                            </td>
                            <td className="text-slate px-5 py-4">{court.capacity} players</td>
                            <td className="px-5 py-4">
                                <StatusBadge label={court.status.label} className={court.status.badgeClass} />
                            </td>
                            <td className="px-5 py-4 text-right">
                                <AdminButton href={`/admin/courts/${court.id}`}>Open</AdminButton>
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}
