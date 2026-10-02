import { usePage } from '@inertiajs/react';
import SectionHeading from '@/components/marketing/SectionHeading';
import BookingCta from '@/components/marketing/BookingCta';
import Seo from '@/components/seo/Seo';
import PageLayout, { EmptyState, Section } from '@/layouts/PageLayout';
import type { SharedProps } from '@/types';

interface EventsProps {
    events: unknown[];
}

/** Event categories supported by the platform (plan.md §16). */
const eventTypes = [
    { title: 'Open play', description: 'Drop-in sessions where you rotate games with other players.' },
    { title: 'Beginner sessions', description: 'Learn the basics in a friendly, no-pressure setting.' },
    { title: 'Clinics and coaching', description: 'Work on technique with a certified coach.' },
    { title: 'Leagues', description: 'Regular season play with divisions by skill level.' },
    { title: 'Community events', description: 'Social games and club events for the whole family.' },
];

export default function Events({ events }: EventsProps) {
    const shared = usePage<SharedProps>().props;

    return (
        <PageLayout shared={shared}>
            <Seo />

            <Section className="py-14">
                <SectionHeading
                    eyebrow="Events"
                    title="Find a game, a clinic or a league"
                    description="Pickleball is better with company. Join sessions and meet players at your level."
                />

                <div className="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    {eventTypes.map((type) => (
                        <article key={type.title} className="border-hairline rounded-card border bg-white p-6">
                            <h2 className="text-pickle-800 font-semibold">{type.title}</h2>
                            <p className="text-slate mt-2 text-sm">{type.description}</p>
                        </article>
                    ))}
                </div>
            </Section>

            <Section className="pb-16">
                {events.length === 0 ? (
                    <EmptyState
                        title="No events scheduled yet"
                        description="Facilities will post open play, clinics and leagues here. Join a facility to be notified as soon as sessions are published."
                        actionHref="/memberships"
                        actionLabel="See memberships"
                    />
                ) : (
                    <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                        {events.map((event) => (
                            <article key={String(event)} className="border-hairline rounded-card border bg-white p-6">
                                {String(event)}
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
