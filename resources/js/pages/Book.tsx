import { router, usePage } from '@inertiajs/react';
import PageLayout, { Section } from '@/layouts/PageLayout';
import Seo from '@/components/seo/Seo';
import type { SharedProps } from '@/types';

interface Slot {
    starts_at: string;
    ends_at: string;
    label: string;
    amount: number | null;
}

interface Court {
    id: number;
    name: string;
    number: string | null;
    capacity: number;
    surface: string;
    setting: string;
    slots: Slot[];
}

interface Branch {
    id: number;
    name: string;
    address_city: string | null;
    address_barangay: string | null;
    courts_count: number;
}

interface BookProps {
    branches: Branch[];
    courts: Court[];
    selectedBranchId: number | null;
    selectedDate: string;
    today: string;
    currencySymbol: string;
}

function peso(amount: number | null, symbol: string): string {
    if (amount === null) return '—';

    return (
        symbol + new Intl.NumberFormat('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(amount)
    );
}

export default function Book({ branches, courts, selectedBranchId, selectedDate, today, currencySymbol }: BookProps) {
    const shared = usePage<SharedProps>().props;

    const navigate = (params: Record<string, string | number | null>) => {
        router.get(
            '/book',
            {
                branch_id: params.branch_id ?? null,
                date: params.date ?? null,
            },
            { preserveState: true, replace: true },
        );
    };

    return (
        <PageLayout shared={shared}>
            <Seo />

            <Section className="py-12">
                <h1 className="font-display text-4xl font-extrabold text-pickle-900 sm:text-5xl">Book a Court</h1>
                <p className="text-slate mt-3 max-w-2xl text-lg">
                    Pick a facility, choose an open time and you are on court. No forms to fill in twice.
                </p>

                {/* Step 1 — facility */}
                <div className="mt-8">
                    <h2 className="text-pickle-900 font-semibold">1. Choose a facility</h2>
                    <div className="mt-3 flex flex-wrap gap-2">
                        {branches.length === 0 && (
                            <p className="text-slate text-sm">No facilities have opened bookings yet.</p>
                        )}
                        {branches.map((branch) => (
                            <button
                                key={branch.id}
                                type="button"
                                onClick={() => navigate({ branch_id: branch.id, date: selectedDate })}
                                className={`rounded-card border px-5 py-3 text-sm font-semibold transition-colors ${
                                    selectedBranchId === branch.id
                                        ? 'border-pickle-500 bg-pickle-50 text-pickle-800'
                                        : 'border-hairline bg-white text-slate hover:bg-pickle-50'
                                }`}
                            >
                                {branch.name}
                                {branch.address_city ? ` · ${branch.address_city}` : ''}
                            </button>
                        ))}
                    </div>
                </div>

                {/* Step 2 — date */}
                <div className="mt-8">
                    <label htmlFor="date" className="text-pickle-900 font-semibold">
                        2. Choose a date
                    </label>
                    <input
                        id="date"
                        type="date"
                        value={selectedDate}
                        min={today}
                        onChange={(e) => navigate({ branch_id: selectedBranchId, date: e.target.value })}
                        className="border-hairline focus:border-pickle-500 focus:ring-pickle-500 mt-3 block rounded-card border bg-white px-4 py-2.5 text-sm"
                    />
                </div>

                {/* Step 3 — court and time */}
                <div className="mt-8">
                    <h2 className="text-pickle-900 font-semibold">3. Pick a court and time</h2>

                    {selectedBranchId === null ? (
                        <p className="text-slate mt-3 text-sm">Choose a facility first.</p>
                    ) : courts.length === 0 ? (
                        <p className="text-slate mt-3 text-sm">
                            This facility has no bookable courts, or none are open on the selected date.
                        </p>
                    ) : (
                        <div className="mt-4 space-y-4">
                            {courts.map((court) => (
                                <article key={court.id} className="border-hairline rounded-panel border bg-white p-6">
                                    <div className="flex flex-wrap items-center justify-between gap-2">
                                        <h3 className="text-pickle-900 font-semibold">
                                            {court.name}
                                            {court.number ? ` (#${court.number})` : ''}
                                        </h3>
                                        <span className="text-slate text-xs capitalize">
                                            {String(court.surface).replace('_', ' ')} · {court.setting} ·{' '}
                                            {court.capacity} players
                                        </span>
                                    </div>

                                    {court.slots.length === 0 ? (
                                        <p className="text-slate mt-3 text-sm">Fully booked on this date.</p>
                                    ) : (
                                        <div className="mt-4 flex flex-wrap gap-2">
                                            {court.slots.map((slot) => (
                                                <button
                                                    key={slot.starts_at}
                                                    type="button"
                                                    onClick={() =>
                                                        router.post(
                                                            '/book',
                                                            {
                                                                court_id: court.id,
                                                                starts_at: slot.starts_at,
                                                                ends_at: slot.ends_at,
                                                            },
                                                            { preserveScroll: true },
                                                        )
                                                    }
                                                    className="border-hairline rounded-card border bg-white px-4 py-2 text-sm font-semibold text-pickle-800 transition-colors hover:bg-pickle-500 hover:text-white"
                                                >
                                                    {slot.label}
                                                    <span className="ml-2 text-xs font-normal text-slate">
                                                        {peso(slot.amount, currencySymbol)}
                                                    </span>
                                                </button>
                                            ))}
                                        </div>
                                    )}
                                </article>
                            ))}
                        </div>
                    )}
                </div>

                <p className="text-slate mt-10 text-sm">
                    Times are shown in Philippine time (Asia/Manila). Payments are collected in Phase 5.
                </p>
            </Section>
        </PageLayout>
    );
}
