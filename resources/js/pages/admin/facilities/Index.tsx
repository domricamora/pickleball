import { Link } from '@inertiajs/react';
import { AdminButton, AdminPage, StatusBadge } from '@/components/admin/AdminUi';

interface Facility {
    id: number;
    name: string;
    status: string;
    address_city: string | null;
    address_barangay: string | null;
    address_region: string | null;
    is_primary: boolean;
    courts_count?: number;
}

export default function Index({ facilities }: { facilities: Facility[] }) {
    return (
        <AdminPage
            title="Facilities"
            subtitle="The locations your organisation operates."
            actions={
                <AdminButton href="/admin/facilities/create" variant="primary">
                    Add facility
                </AdminButton>
            }
        >
            {facilities.length === 0 ? (
                <div className="border-hairline rounded-panel border border-dashed bg-white px-6 py-16 text-center">
                    <h2 className="font-display text-2xl font-bold text-pickle-900">No facilities yet</h2>
                    <p className="text-slate mx-auto mt-2 max-w-md">
                        Add your first court location, then add the courts inside it.
                    </p>
                    <Link
                        href="/admin/facilities/create"
                        className="bg-energetic-500 hover:bg-energetic-600 mt-6 inline-block rounded-card px-6 py-3 font-semibold text-white transition-colors"
                    >
                        Add facility
                    </Link>
                </div>
            ) : (
                <div className="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                    {facilities.map((facility) => (
                        <article key={facility.id} className="border-hairline rounded-panel border bg-white p-6">
                            <div className="flex items-start justify-between gap-3">
                                <h2 className="text-pickle-900 font-semibold">{facility.name}</h2>
                                <StatusBadge
                                    label={facility.status}
                                    className={
                                        facility.status === 'active'
                                            ? 'bg-pickle-50 text-pickle-700'
                                            : 'bg-energetic-50 text-energetic-700'
                                    }
                                />
                            </div>

                            <address className="text-slate mt-2 text-sm not-italic">
                                {[facility.address_barangay, facility.address_city, facility.address_region]
                                    .filter(Boolean)
                                    .join(', ') || 'No address set'}
                            </address>

                            <p className="text-slate mt-3 text-sm">
                                {facility.courts_count ?? 0} court{(facility.courts_count ?? 0) === 1 ? '' : 's'}
                            </p>

                            <div className="mt-5 flex gap-2">
                                <AdminButton href={`/admin/facilities/${facility.id}/edit`}>Edit</AdminButton>
                                <AdminButton href={`/admin/courts?branch_id=${facility.id}`}>View courts</AdminButton>
                            </div>
                        </article>
                    ))}
                </div>
            )}
        </AdminPage>
    );
}
