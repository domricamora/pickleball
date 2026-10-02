import { usePage } from '@inertiajs/react';
import BookingCta from '@/components/marketing/BookingCta';
import Seo from '@/components/seo/Seo';
import PageLayout, { EmptyState, PageHero, Section } from '@/layouts/PageLayout';
import type { SharedProps } from '@/types';

interface FacilitiesProps {
    filters: Record<string, string>;
    coverage: string[];
    facilities: unknown[];
}

export default function Facilities({ filters, coverage, facilities }: FacilitiesProps) {
    const shared = usePage<SharedProps>().props;

    return (
        <PageLayout shared={shared}>
            <Seo />

            <PageHero
                eyebrow="Facilities"
                title="Find a court near you"
                description="Search by city, court type and availability to find a court that fits your next match."
                image="/media/courts-outdoor.webp"
                alt="Outdoor pickleball courts beside a paved path"
            >
                <div className="flex flex-wrap gap-2">
                    {Object.entries(filters).map(([key, label]) => (
                        <button
                            key={key}
                            type="button"
                            className="border-hairline-dark text-mist-200 hover:border-lime-accent hover:text-lime-accent rounded-pill border px-5 py-2.5 text-sm font-medium transition-colors"
                        >
                            {label}
                        </button>
                    ))}
                </div>
            </PageHero>

            <Section className="py-20 sm:py-24">
                {facilities.length === 0 ? (
                    <EmptyState
                        title="No facilities listed yet"
                        description="Courts are being added across the Philippines right now. In the meantime, explore how booking works or get in touch if you operate a facility."
                        actionHref="/contact"
                        actionLabel="Get in touch"
                    />
                ) : (
                    <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                        {facilities.map((facility) => (
                            <article
                                key={String(facility)}
                                className="bg-night-850 border-hairline-dark rounded-card border p-6"
                            >
                                {String(facility)}
                            </article>
                        ))}
                    </div>
                )}

                <div className="mt-14">
                    <h2 className="font-display text-mist-50 text-2xl sm:text-3xl">Cities we cover</h2>
                    <ul className="mt-6 flex flex-wrap gap-3">
                        {coverage.map((city) => (
                            <li key={city} className="bg-night-850 text-mist-200 rounded-pill px-5 py-2.5 text-sm">
                                {city}
                            </li>
                        ))}
                    </ul>
                </div>
            </Section>

            <Section className="pb-20 sm:pb-24">
                <BookingCta />
            </Section>
        </PageLayout>
    );
}
