import { router } from '@inertiajs/react';
import { AdminPage, StatusBadge } from '@/components/admin/AdminUi';
import { withBasePath } from '@/lib/format';

interface Kpis {
    revenue: string;
    expenses: string;
    profit: string;
    bookings: number;
    average_booking_value: string;
    active_members: number;
    new_customers: number;
    profit_is_positive: boolean;
}

interface MoneyRow {
    label: string;
    amount: string;
}

interface TodayBooking {
    id: number;
    reference: string;
    court: string;
    time: string;
    player: string;
    status: { label: string; badgeClass: string };
}

interface UtilisationRow {
    court: string;
    percent: number;
}

interface BranchRow {
    id: number;
    name: string;
    status: string;
    courts: number;
}

interface DashboardProps {
    range: number;
    ranges: number[];
    window: { from: string; to: string; label: string };
    kpis: Kpis;
    revenue_by_source: MoneyRow[];
    todays_bookings: TodayBooking[];
    utilisation: UtilisationRow[];
    branches: BranchRow[];
}

export default function Dashboard({
    range,
    ranges,
    window,
    kpis,
    revenue_by_source,
    todays_bookings,
    utilisation,
    branches,
}: DashboardProps) {
    const setRange = (days: number) => {
        router.get(withBasePath('/admin'), { range: days }, { preserveState: true, replace: true });
    };

    const tiles = [
        { label: 'Revenue', value: kpis.revenue, tone: 'text-pickle-700' },
        { label: 'Expenses', value: kpis.expenses, tone: 'text-slate' },
        {
            label: 'Profit',
            value: kpis.profit,
            tone: kpis.profit_is_positive ? 'text-pickle-700' : 'text-energetic-700',
        },
        { label: 'Bookings', value: String(kpis.bookings), tone: 'text-pickle-800' },
        { label: 'Avg. booking', value: kpis.average_booking_value, tone: 'text-pickle-800' },
        { label: 'Active members', value: String(kpis.active_members), tone: 'text-pickle-800' },
        { label: 'New customers', value: String(kpis.new_customers), tone: 'text-pickle-800' },
    ];

    return (
        <AdminPage
            title="Dashboard"
            subtitle={`${window.label} · all facilities you can see`}
            actions={<RangePicker ranges={ranges} active={range} onSelect={setRange} />}
        >
            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                {tiles.map((tile) => (
                    <div key={tile.label} className="border-hairline rounded-card border bg-white p-5">
                        <p className="text-slate text-sm font-medium">{tile.label}</p>
                        <p className={`font-display mt-1 truncate text-2xl font-extrabold ${tile.tone}`}>
                            <div className="mt-6 grid gap-6 lg:grid-cols-2">
                                <section>
                                    <h2 className="font-display text-lg font-bold text-pickle-900">
                                        Today&rsquo;s schedule
                                    </h2>
                                    <p className="text-slate mt-1 text-sm">Bookings still holding a court slot.</p>
                                    {todays_bookings.length === 0 ? (
                                        <EmptyPanel
                                            title="No bookings today"
                                            body="Nothing is on the schedule right now."
                                        />
                                    ) : (
                                        <ul className="mt-3 space-y-2">
                                            {todays_bookings.map((booking) => (
                                                <li
                                                    key={booking.id}
                                                    className="border-hairline flex flex-wrap items-center justify-between gap-3 rounded-card border bg-white px-5 py-4"
                                                >
                                                    <div className="min-w-0">
                                                        <p className="text-pickle-900 font-semibold">
                                                            {booking.time}
                                                            <span className="text-slate ml-2 font-normal">
                                                                {booking.court}
                                                            </span>
                                                        </p>
                                                        <p className="text-slate truncate text-sm">
                                                            {booking.player} · {booking.reference}
                                                        </p>
                                                    </div>
                                                    <StatusBadge
                                                        label={booking.status.label}
                                                        className={booking.status.badgeClass}
                                                    />
                                                </li>
                                            ))}
                                        </ul>
                                    )}
                                </section>

                                <section>
                                    <h2 className="font-display text-lg font-bold text-pickle-900">Revenue mix</h2>
                                    <p className="text-slate mt-1 text-sm">Where the money came from.</p>
                                    <ul className="border-hairline mt-3 divide-hairline divide-y rounded-panel border bg-white">
                                        {revenue_by_source.map((row) => (
                                            <li key={row.label} className="flex items-center justify-between px-5 py-4">
                                                <span className="text-pickle-900 text-sm font-medium">{row.label}</span>
                                                <span className="font-display text-pickle-700 font-bold">
                                                    {row.amount}
                                                </span>
                                            </li>
                                        ))}
                                    </ul>
                                </section>
                            </div>
                            {tile.value}
                        </p>
                    </div>
                ))}
            </div>
            <UtilisationSection rows={utilisation} />

            <section className="mt-8">
                <h2 className="font-display text-lg font-bold text-pickle-900">Your facilities</h2>
                {branches.length === 0 ? (
                    <EmptyPanel title="No facilities yet" body="Add your first facility to start taking bookings." />
                ) : (
                    <ul className="border-hairline mt-3 divide-hairline divide-y rounded-panel border bg-white">
                        {branches.map((branch) => (
                            <li key={branch.id} className="flex items-center justify-between px-5 py-4">
                                <div>
                                    <p className="text-pickle-900 font-semibold">{branch.name}</p>
                                    <p className="text-slate text-sm">
                                        {branch.courts} {branch.courts === 1 ? 'court' : 'courts'}
                                    </p>
                                </div>
                                <StatusBadge
                                    label={branch.status}
                                    className={
                                        branch.status === 'active'
                                            ? 'bg-pickle-50 text-pickle-700'
                                            : 'bg-energetic-50 text-energetic-700'
                                    }
                                />
                            </li>
                        ))}
                    </ul>
                )}
            </section>
        </AdminPage>
    );
}
function RangePicker({
    ranges,
    active,
    onSelect,
}: {
    ranges: number[];
    active: number;
    onSelect: (days: number) => void;
}) {
    return (
        <div className="border-hairline flex rounded-card border bg-white p-1">
            {ranges.map((days) => (
                <button
                    key={days}
                    type="button"
                    onClick={() => onSelect(days)}
                    aria-pressed={days === active}
                    className={`rounded-card px-3 py-1.5 text-sm font-semibold transition-colors ${
                        days === active ? 'bg-pickle-700 text-white' : 'text-pickle-800 hover:bg-pickle-50'
                    }`}
                >
                    {days}d
                </button>
            ))}
        </div>
    );
}

function EmptyPanel({ title, body }: { title: string; body: string }) {
    return (
        <div className="border-hairline rounded-panel mt-3 border border-dashed bg-white px-6 py-10 text-center">
            <p className="text-pickle-900 font-semibold">{title}</p>
            <p className="text-slate mt-1 text-sm">{body}</p>
        </div>
    );
}

function UtilisationSection({ rows }: { rows: UtilisationRow[] }) {
    return (
        <section className="mt-8">
            <h2 className="font-display text-lg font-bold text-pickle-900">Court utilisation</h2>
            <p className="text-slate mt-1 text-sm">Booked hours against hours available.</p>
            {rows.length === 0 ? (
                <EmptyPanel title="No courts yet" body="Add courts to see how hard they are working." />
            ) : (
                <ul className="mt-3 space-y-3">
                    {rows.map((row) => (
                        <li key={row.court} className="border-hairline rounded-card border bg-white px-5 py-4">
                            <div className="flex items-center justify-between text-sm">
                                <span className="text-pickle-900 font-semibold">{row.court}</span>
                                <span className="text-slate font-medium">{row.percent}%</span>
                            </div>
                            <div
                                className="bg-slate-100 mt-2 h-2 overflow-hidden rounded-full"
                                role="meter"
                                aria-valuenow={row.percent}
                                aria-valuemin={0}
                                aria-valuemax={100}
                                aria-label={`${row.court} utilisation`}
                            >
                                <div
                                    className="bg-pickle-500 h-full rounded-full"
                                    style={{ width: `${Math.min(100, Math.max(0, row.percent))}%` }}
                                />
                            </div>
                        </li>
                    ))}
                </ul>
            )}
        </section>
    );
}
