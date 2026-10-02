import { usePage } from '@inertiajs/react';
import Faq from '@/components/marketing/Faq';
import SectionHeading from '@/components/marketing/SectionHeading';
import BookingCta from '@/components/marketing/BookingCta';
import Seo from '@/components/seo/Seo';
import PageLayout, { Section } from '@/layouts/PageLayout';
import type { SharedProps } from '@/types';

interface FaqPageProps {
    items: Array<[string, string]>;
}

export default function FaqPage({ items }: FaqPageProps) {
    const shared = usePage<SharedProps>().props;

    return (
        <PageLayout shared={shared}>
            <Seo />

            <Section className="py-20 sm:py-28">
                <div className="grid gap-12 lg:grid-cols-[minmax(0,1fr)_320px] lg:gap-16">
                    <div>
                        <SectionHeading
                            eyebrow="FAQ"
                            title="Frequently asked questions"
                            description="Everything players ask before their first booking."
                        />
                        <div className="mt-12">
                            <Faq items={items.map(([question, answer]) => ({ question, answer }))} />
                        </div>
                    </div>

                    <aside className="bg-night-850 border-hairline-dark h-fit rounded-card border p-6">
                        <h2 className="font-display-plain text-mist-50 text-lg">Still have a question?</h2>
                        <p className="text-mist-400 mt-2 text-sm leading-relaxed">
                            Our team is happy to help with booking, membership or facility questions.
                        </p>
                        <a
                            href={`mailto:${shared.contact.email}`}
                            className="btn-pill btn-pill-primary mt-6 px-5 py-2.5 text-xs"
                        >
                            Contact us
                        </a>
                    </aside>
                </div>
            </Section>

            <Section className="pb-20 sm:pb-28">
                <BookingCta />
            </Section>
        </PageLayout>
    );
}
