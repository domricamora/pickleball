import { router } from '@inertiajs/react';
import { ArrowUpRight, CalendarDays, Clock, Users } from 'lucide-react';
import { useState } from 'react';
import { withBasePath } from '@/lib/format';

/** Bookable start times, in 24-hour Manila time. */
const times = ['06:00', '08:00', '10:00', '14:00', '16:00', '18:00', '20:00'];

const playerCounts = [2, 3, 4];

/** Formats a 24-hour "HH:mm" as a 12-hour clock time for display. */
function label(time: string): string {
    const [hours, minutes] = time.split(':').map(Number);
    const suffix = hours >= 12 ? 'PM' : 'AM';
    const hour12 = hours % 12 === 0 ? 12 : hours % 12;

    return `${hour12}:${String(minutes).padStart(2, '0')} ${suffix}`;
}

/** Manila-local today as `YYYY-MM-DD`, so the input never offers a past date. */
function manilaToday(): string {
    return new Intl.DateTimeFormat('en-CA', { timeZone: 'Asia/Manila' }).format(new Date());
}

interface FieldProps {
    id: string;
    label: string;
    icon: React.ReactNode;
    children: React.ReactNode;
}

/**
 * One labelled row in the widget.
 *
 * The label is a real `<label for>` rather than a placeholder, so clicking it
 * focuses the control and screen readers announce it.
 */
function Field({ id, label, icon, children }: FieldProps) {
    return (
        <div>
            <label htmlFor={id} className="eyebrow text-mist-400 flex items-center gap-1.5">
                <span className="text-lime-accent" aria-hidden>
                    {icon}
                </span>
                {label}
            </label>
            <div className="mt-2">{children}</div>
        </div>
    );
}

const controlClass =
    'w-full appearance-none rounded-card border border-hairline-dark bg-night-950 px-4 py-3 text-sm text-mist-50 ' +
    'transition-colors hover:border-night-600 focus:border-lime-accent focus:outline-none';

/**
 * The hero's "Find your court" panel.
 *
 * It is a real control, not a decorative mock-up: selecting a date and time
 * navigates to the booking funnel with those values applied, which is what the
 * reference design's widget does. Availability itself is resolved by the
 * booking engine, so the panel deliberately promises nothing about counts.
 */
export default function BookingWidget() {
    const today = manilaToday();
    const [date, setDate] = useState(today);
    const [time, setTime] = useState(times[3]);
    const [players, setPlayers] = useState(playerCounts[0]);

    const submit = (event: React.FormEvent) => {
        event.preventDefault();

        router.get(withBasePath('/book'), { date, time, players }, { preserveScroll: false });
    };

    return (
        <form
            onSubmit={submit}
            className="bg-night-850/95 rounded-panel border-hairline-dark border p-6 backdrop-blur-sm sm:p-7"
            aria-label="Find your court"
        >
            <h2 className="font-display-plain text-mist-50 text-lg">Find your court</h2>

            <div className="mt-5 space-y-4">
                <Field id="court-date" label="Date" icon={<CalendarDays size={13} />}>
                    <input
                        id="court-date"
                        name="date"
                        type="date"
                        value={date}
                        min={today}
                        onChange={(event) => setDate(event.target.value)}
                        className={controlClass}
                    />
                </Field>

                <Field id="court-time" label="Time" icon={<Clock size={13} />}>
                    <select
                        id="court-time"
                        name="time"
                        value={time}
                        onChange={(event) => setTime(event.target.value)}
                        className={controlClass}
                    >
                        {times.map((option) => (
                            <option key={option} value={option}>
                                {label(option)}
                            </option>
                        ))}
                    </select>
                </Field>

                <Field id="court-players" label="Players" icon={<Users size={13} />}>
                    <select
                        id="court-players"
                        name="players"
                        value={players}
                        onChange={(event) => setPlayers(Number(event.target.value))}
                        className={controlClass}
                    >
                        {playerCounts.map((count) => (
                            <option key={count} value={count}>
                                {count} players
                            </option>
                        ))}
                    </select>
                </Field>
            </div>

            <button type="submit" className="btn-pill btn-pill-primary mt-5 w-full py-3.5 text-[0.8125rem]">
                Check availability
                <ArrowUpRight size={16} strokeWidth={2.5} aria-hidden />
            </button>

            <p className="text-mist-500 mt-4 text-center text-xs">No account needed to check a time slot.</p>
        </form>
    );
}
