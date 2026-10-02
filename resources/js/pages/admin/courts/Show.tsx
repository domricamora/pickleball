import { router } from '@inertiajs/react';
import { AdminButton, AdminPage, StatusBadge } from '@/components/admin/AdminUi';
import { peso } from '@/lib/format';

interface ShowProps {
    court: {
        id: number;
        name: string;
        number: string | null;
        capacity: number;
        status: { label: string; badgeClass: string };
        surface: { label: string };
        setting: string;
        notes: string | null;
        branch: { id: number; name: string; address_city: string | null };
        schedules: Array<{ id: number; weekday: number; opens_at: string; closes_at: string; is_closed: boolean }>;
        blocks: Array<{
            id: number;
            type: { label: string };
            reason: string | null;
            starts_on: string;
            ends_on: string;
        }>;
        prices: Array<{ id: number; type: { label: string }; amount: string; is_active: boolean }>;
    };
    weekdays: string[];
}

export default function Show({ court, weekdays }: ShowProps) {
    const remove = () => {
        if (confirm(`Remove ${court.name}?`)) {
            router.delete(`/admin/courts/${court.id}`);
        }
    };

    return (
        <AdminPage
            title={court.name}
            subtitle={`${court.branch?.name}${court.branch?.address_city ? ` · ${court.branch.address_city}` : ''}`}
            actions={
                <>
                    <AdminButton href={`/admin/courts/${court.id}/edit`}>Edit</AdminButton>
                    <button
                        type="button"
                        onClick={remove}
                        className="border-energetic-200 text-energetic-700 hover:bg-energetic-50 rounded-card border bg-white px-4 py-2 text-sm font-semibold transition-colors"
                    >
                        Remove
                    </button>
                </>
            }
        >
            <div className="grid gap-5 lg:grid-cols-3">
                <div className="border-hairline rounded-panel border bg-white p-6 lg:col-span-1">
                    <h2 className="text-pickle-900 font-semibold">Details</h2>
                    <dl className="mt-4 space-y-3 text-sm">
                        <div>
                            <dt className="text-slate">Status</dt>
                            <dd className="mt-1">
                                <StatusBadge label={court.status.label} className={court.status.badgeClass} />
                            </dd>
                        </div>
                        <div>
                            <dt className="text-slate">Surface</dt>
                            <dd className="text-pickle-900">
                                {court.surface?.label} · {court.setting}
                            </dd>
                        </div>
                        <div>
                            <dt className="text-slate">Capacity</dt>
                            <dd className="text-pickle-900">{court.capacity} players</dd>
                        </div>
                        {court.number && (
                            <div>
                                <dt className="text-slate">Number</dt>
                                <dd className="text-pickle-900">#{court.number}</dd>
                            </div>
                        )}
                        {court.notes && (
                            <div>
                                <dt className="text-slate">Notes</dt>
                                <dd className="text-pickle-900">{court.notes}</dd>
                            </div>
                        )}
                    </dl>
                </div>

                <div className="border-hairline rounded-panel border bg-white p-6">
                    <h2 className="text-pickle-900 font-semibold">Weekly hours</h2>
                    {court.schedules.length === 0 ? (
                        <p className="text-slate mt-3 text-sm">
                            No weekly hours set yet. Availability falls back to the facility opening hours.
                        </p>
                    ) : (
                        <ul className="mt-4 space-y-2 text-sm">
                            {court.schedules.map((schedule) => (
                                <li key={schedule.id} className="flex items-center justify-between">
                                    <span className="text-pickle-900">{weekdays[schedule.weekday]}</span>
                                    <span className="text-slate">
                                        {schedule.is_closed
                                            ? 'Closed'
                                            : `${schedule.opens_at.slice(0, 5)} – ${schedule.closes_at.slice(0, 5)}`}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>

                <div className="border-hairline rounded-panel border bg-white p-6">
                    <h2 className="text-pickle-900 font-semibold">Pricing</h2>
                    {court.prices.length === 0 ? (
                        <p className="text-slate mt-3 text-sm">
                            No prices published. This court cannot be booked until a rate exists.
                        </p>
                    ) : (
                        <ul className="mt-4 space-y-2 text-sm">
                            {court.prices.map((price) => (
                                <li key={price.id} className="flex items-center justify-between">
                                    <span className="text-pickle-900">{price.type.label}</span>
                                    <span className="font-semibold text-pickle-700">
                                        {peso(Number(price.amount))}
                                        {!price.is_active && (
                                            <span className="text-slate ml-2 text-xs font-normal">(inactive)</span>
                                        )}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>
            </div>

            <div className="border-hairline mt-5 rounded-panel border bg-white p-6">
                <h2 className="text-pickle-900 font-semibold">Upcoming blocks</h2>
                {court.blocks.length === 0 ? (
                    <p className="text-slate mt-3 text-sm">No maintenance, holidays or reservations.</p>
                ) : (
                    <ul className="mt-4 space-y-2 text-sm">
                        {court.blocks.map((block) => (
                            <li key={block.id} className="flex flex-wrap items-center justify-between gap-2">
                                <span className="text-pickle-900">
                                    {block.type.label}
                                    {block.reason ? ` — ${block.reason}` : ''}
                                </span>
                                <span className="text-slate">
                                    {block.starts_on} to {block.ends_on}
                                </span>
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </AdminPage>
    );
}
