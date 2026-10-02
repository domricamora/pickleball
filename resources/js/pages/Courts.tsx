import { usePage } from '@inertiajs/react';
import SectionHeading from '@/components/marketing/SectionHeading';
import BookingCta from '@/components/marketing/BookingCta';
import Seo from '@/components/seo/Seo';
import PageLayout, { EmptyState, Section } from '@/layouts/PageLayout';
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

            <Section className="py-14">
                <SectionHeading
                    eyebrow="Courts"
                    title="Courts for every kind of player"
                    description="From a first game to league night, book a court that suits how you play."
                />
            </Section>

            <Section className="pb-16">
                <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    {courtTypes.map((type) => (
                        <article key={type.title} className="border-hairline rounded-card border bg-white p-6">
                            <h2 className="text-pickle-800 font-semibold">{type.title}</h2>
                            <p className="text-slate mt-2 text-sm">{type.description}</p>
                        </article>
                    ))}
                </div>

                <div className="mt-10">
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
                                    className="border-hairline rounded-card border bg-white p-6"
                                >
                                    {String(court)}
                                </article>
                            ))}
                        </div>
                    )}
                </div>
            </Section>

            <Section className="pb-16">
                <BookingCta />
            </Section>
        </PageLayout>
    );
}
