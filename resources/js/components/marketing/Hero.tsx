import { Link } from '@inertiajs/react';
import { CalendarCheck, MapPin } from 'lucide-react';

interface HeroProps {
    headline: string;
    tagline: string;
    description: string;
    coverage: string[];
}

/**
 * Homepage hero (plan.md §4). Imagery is added in Phase 1's media pass; the
 * court-line grid stands in until licensed photography is credited.
 */
export default function Hero({ headline, tagline, description, coverage }: HeroProps) {
    return (
        <section className="bg-pickle-900 text-white relative overflow-hidden">
            <div
                className="pointer-events-none absolute inset-0 opacity-25"
                style={{
                    backgroundImage:
                        'linear-gradient(to right, rgba(255,255,255,.14) 1px, transparent 1px), linear-gradient(to bottom, rgba(255,255,255,.14) 1px, transparent 1px)',
                    backgroundSize: '56px 56px',
                }}
                aria-hidden
            />

            <div className="relative mx-auto grid w-full max-w-7xl gap-12 px-4 py-20 sm:px-6 sm:py-28 lg:grid-cols-2 lg:items-center lg:px-8">
                <div>
                    <span className="bg-lime-accent text-pickle-900 rounded-full px-4 py-1.5 text-xs font-bold tracking-wide uppercase">
                        Philippines
                    </span>

                    <h1 className="font-display mt-6 text-4xl font-extrabold sm:text-6xl">{headline}</h1>

                    <p className="mt-6 max-w-xl text-lg text-pickle-100">{description}</p>

                    <p className="text-lime-accent mt-4 font-semibold tracking-widest uppercase">{tagline}</p>

                    <div className="mt-10 flex flex-col gap-3 sm:flex-row">
                        <Link
                            href="/book"
                            className="bg-energetic-500 hover:bg-energetic-600 inline-flex items-center justify-center gap-2 rounded-card px-7 py-3.5 font-semibold text-white transition-colors"
                        >
                            <CalendarCheck size={18} aria-hidden />
                            Book a Court
                        </Link>
                        <Link
                            href="/facilities"
                            className="border-pickle-600 hover:bg-pickle-800 inline-flex items-center justify-center rounded-card border px-7 py-3.5 font-semibold text-white transition-colors"
                        >
                            Explore Facilities
                        </Link>
                    </div>

                    {coverage.length > 0 && (
                        <p className="text-pickle-300 mt-8 flex items-center gap-2 text-sm">
                            <MapPin size={15} aria-hidden />
                            {coverage.join(' · ')}
                        </p>
                    )}
                </div>

                <div className="hidden lg:block">
                    <HeroStat />
                </div>
            </div>
        </section>
    );
}

function HeroStat() {
    const points = [
        { label: 'Real-time availability', value: 'Live' },
        { label: 'Booking steps', value: '3' },
        { label: 'Payment options', value: 'GCash · Maya · Cash' },
    ];

    return (
        <div className="border-pickle-700 bg-pickle-800/60 rounded-panel border p-8 backdrop-blur">
            <h2 className="text-lime-accent text-sm font-bold tracking-widest uppercase">Play. Book. Compete.</h2>
            <dl className="mt-6 space-y-5">
                {points.map((point) => (
                    <div key={point.label}>
                        <dt className="text-pickle-200 text-sm">{point.label}</dt>
                        <dd className="font-display text-2xl font-extrabold text-white">{point.value}</dd>
                    </div>
                ))}
            </dl>
        </div>
    );
}
