import { Link } from '@inertiajs/react';
import { CalendarCheck } from 'lucide-react';

interface BookingCtaProps {
    title?: string;
    description?: string;
    primaryLabel?: string;
    primaryHref?: string;
    secondaryLabel?: string;
    secondaryHref?: string;
}

/**
 * The platform's primary call to action (plan.md §4). Reused on the home page
 * and on long-form marketing pages where the reader needs a next step.
 */
export default function BookingCta({
    title = 'Ready to Play?',
    description = 'Book your court today and get on the court in minutes.',
    primaryLabel = 'Book a Court',
    primaryHref = '/book',
    secondaryLabel = 'Explore Facilities',
    secondaryHref = '/facilities',
}: BookingCtaProps) {
    return (
        <section className="bg-pickle-900 rounded-panel relative overflow-hidden px-6 py-14 text-white sm:px-12">
            <div
                className="pointer-events-none absolute inset-0 opacity-20"
                style={{
                    backgroundImage:
                        'linear-gradient(to right, rgba(255,255,255,.16) 1px, transparent 1px), linear-gradient(to bottom, rgba(255,255,255,.16) 1px, transparent 1px)',
                    backgroundSize: '44px 44px',
                }}
                aria-hidden
            />
            <div className="relative flex flex-col items-start gap-8 lg:flex-row lg:items-center lg:justify-between">
                <div className="max-w-xl">
                    <h2 className="font-display text-3xl font-extrabold sm:text-4xl">{title}</h2>
                    <p className="mt-3 text-lg text-pickle-100">{description}</p>
                </div>

                <div className="flex w-full flex-col gap-3 sm:w-auto sm:flex-row">
                    <Link
                        href={primaryHref}
                        className="bg-energetic-500 hover:bg-energetic-600 inline-flex items-center justify-center gap-2 rounded-card px-7 py-3.5 font-semibold text-white transition-colors"
                    >
                        <CalendarCheck size={18} aria-hidden />
                        {primaryLabel}
                    </Link>
                    <Link
                        href={secondaryHref}
                        className="border-pickle-600 hover:bg-pickle-800 inline-flex items-center justify-center rounded-card border px-7 py-3.5 font-semibold text-white transition-colors"
                    >
                        {secondaryLabel}
                    </Link>
                </div>
            </div>
        </section>
    );
}
