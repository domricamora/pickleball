import { usePage } from '@inertiajs/react';
import BookingCta from '@/components/marketing/BookingCta';
import Seo from '@/components/seo/Seo';
import PageLayout, { EmptyState, PageHero, Section } from '@/layouts/PageLayout';
import type { SharedProps } from '@/types';

interface TournamentsProps {
    tournaments: unknown[];
}

/** Formats the platform supports (plan.md §16). */
const formats = ['Singles', 'Doubles', 'Mixed doubles'];

export default function Tournaments({ tournaments }: TournamentsProps) {
    const shared = usePage<SharedProps>().props;

    return (
        <PageLayout shared={shared}>
            <Seo />

            <PageHero
                eyebrow="Tournaments"
                title="Compete, or cheer from the sideline"
                description="Leagues and tournaments with skill divisions, brackets, match schedules and published results."
                image="/media/courts-sky.webp"
                alt="A row of outdoor pickleball courts running back under a wide sky"
            />

            <Section className="py-20 sm:py-24">
                <div className="grid gap-5 sm:grid-cols-3">
                    {formats.map((format) => (
                        <article key={format} className="bg-night-850 border-hairline-dark rounded-card border p-6">
                            <h2 className="font-display-plain text-mist-50 tracking-wide uppercase">{format}</h2>
                            <p className="text-mist-400 mt-2 text-sm leading-relaxed">
                                Division play with automated brackets and standings.
                            </p>
                        </article>
                    ))}
                </div>
            </Section>

            <Section className="pb-20 sm:pb-24">
                {tournaments.length === 0 ? (
                    <EmptyState
                        title="No tournaments announced yet"
                        description="Tournaments are organised per facility and announced here with registration, divisions and schedules."
                        actionHref="/events"
                        actionLabel="Browse events"
                    />
                ) : (
                    <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                        {tournaments.map((tournament) => (
                            <article
                                key={String(tournament)}
                                className="bg-night-850 border-hairline-dark rounded-card border p-6"
                            >
                                {String(tournament)}
                            </article>
                        ))}
                    </div>
                )}
            </Section>

            <Section className="pb-20 sm:pb-24">
                <BookingCta secondaryLabel="Browse events" secondaryHref="/events" />
            </Section>
        </PageLayout>
    );
}
