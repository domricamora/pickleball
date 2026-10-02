import { usePage } from '@inertiajs/react';
import SectionHeading from '@/components/marketing/SectionHeading';
import BookingCta from '@/components/marketing/BookingCta';
import Seo from '@/components/seo/Seo';
import PageLayout, { EmptyState, Section } from '@/layouts/PageLayout';
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

            <Section className="py-14">
                <SectionHeading
                    eyebrow="Facilities"
                    title="Find a court near you"
                    description="Search by city, court type and availability to find a court that fits your next match."
                />

                <div className="mt-8 flex flex-wrap gap-3">
                    {Object.entries(filters).map(([key, label]) => (
                        <button
                            key={key}
                            type="button"
                            className="border-hairline rounded-full border bg-white px-5 py-2.5 text-sm font-medium text-slate hover:bg-pickle-50"
                        >
                            {label}
                        </button>
                    ))}
                </div>
            </Section>

            <Section className="pb-16">
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
                                className="border-hairline rounded-card border bg-white p-6"
                            >
                                {String(facility)}
                            </article>
                        ))}
                    </div>
                )}

                <div className="mt-10">
                    <h2 className="font-display text-xl font-bold text-pickle-900">Cities we cover</h2>
                    <ul className="mt-4 flex flex-wrap gap-3">
                        {coverage.map((city) => (
                            <li
                                key={city}
                                className="bg-pickle-50 text-pickle-800 rounded-full px-5 py-2.5 text-sm font-semibold"
                            >
                                {city}
                            </li>
                        ))}
                    </ul>
                </div>
            </Section>

            <Section className="pb-16">
                <BookingCta />
            </Section>
        </PageLayout>
    );
}
