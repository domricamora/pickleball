import { usePage } from '@inertiajs/react';
import BookingCta from '@/components/marketing/BookingCta';
import Seo from '@/components/seo/Seo';
import PageLayout, { EmptyState, PageHero, Section } from '@/layouts/PageLayout';
import type { SharedProps } from '@/types';

interface CourtsProps {
    facilities: unknown[];
}

/** Court types a facility can publish (plan.md §11). */
const courtTypes = [
    { title: 'Indoor', description: 'Covered courts, comfortable in any weather.' },
    { title: 'Outdoor', description: 'Open-air courts with natural light and breeze.' },
    { title: 'Hard court', description: 'Durable concrete or acrylic surfaces.' },
    { title: 'Tournament grade', description: 'Regulation surfaces for league and club play.' },
];

export default function Courts({ facilities }: CourtsProps) {
    const shared = usePage<SharedProps>().props;

    return (
        <PageLayout shared={shared}>
            <Seo />

            <PageHero
                eyebrow="Courts"
                title="Courts for every kind of player"
                description="From a first game to league night, book a court that suits how you play."
                image="/media/courts-row.webp"
                alt="A row of pickleball courts behind perimeter fencing"
            />

            <Section className="py-20 sm:py-24">
                <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    {courtTypes.map((type) => (
                        <article key={type.title} className="bg-night-850 border-hairline-dark rounded-card border p-6">
                            <h2 className="font-display-plain text-mist-50 tracking-wide uppercase">{type.title}</h2>
                            <p className="text-mist-400 mt-2 text-sm leading-relaxed">{type.description}</p>
                        </article>
                    ))}
                </div>

                <div className="mt-14">
                    {facilities.length === 0 ? (
                        <EmptyState
                            title="Courts appear here once facilities go live"
                            description="Bookable courts will be listed with their location, surface, amenities and live availability."
                            actionHref="/book"
                            actionLabel="See how booking works"
                        />
                    ) : (
                        <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                            {facilities.map((court) => (
                                <article
                                    key={String(court)}
                                    className="bg-night-850 border-hairline-dark rounded-card border p-6"
                                >
                                    {String(court)}
                                </article>
                            ))}
                        </div>
                    )}
                </div>
            </Section>

            <Section className="pb-20 sm:pb-24">
                <BookingCta />
            </Section>
        </PageLayout>
    );
}
