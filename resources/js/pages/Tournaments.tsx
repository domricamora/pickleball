import { usePage } from '@inertiajs/react';
import SectionHeading from '@/components/marketing/SectionHeading';
import BookingCta from '@/components/marketing/BookingCta';
import Seo from '@/components/seo/Seo';
import PageLayout, { EmptyState, Section } from '@/layouts/PageLayout';
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

            <Section className="py-14">
                <SectionHeading
                    eyebrow="Tournaments"
                    title="Compete, or cheer from the sideline"
                    description="Leagues and tournaments with skill divisions, brackets, match schedules and published results."
                />

                <div className="mt-10 grid gap-5 sm:grid-cols-3">
                    {formats.map((format) => (
                        <article key={format} className="border-hairline rounded-card border bg-white p-6">
                            <h2 className="text-pickle-800 font-semibold">{format}</h2>
                            <p className="text-slate mt-2 text-sm">
                                Division play with automated brackets and standings.
                            </p>
                        </article>
                    ))}
                </div>
            </Section>

            <Section className="pb-16">
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
                                className="border-hairline rounded-card border bg-white p-6"
                            >
                                {String(tournament)}
                            </article>
                        ))}
                    </div>
                )}
            </Section>

            <Section className="pb-16">
                <BookingCta />
            </Section>
        </PageLayout>
    );
}
