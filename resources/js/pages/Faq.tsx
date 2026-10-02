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

            <Section className="py-14">
                <div className="grid gap-12 lg:grid-cols-[minmax(0,1fr)_320px]">
                    <div>
                        <SectionHeading
                            eyebrow="FAQ"
                            title="Frequently asked questions"
                            description="Everything players ask before their first booking."
                        />
                        <div className="mt-10">
                            <Faq items={items.map(([question, answer]) => ({ question, answer }))} />
                        </div>
                    </div>

                    <aside className="border-hairline h-fit rounded-card border bg-white p-6">
                        <h2 className="text-pickle-900 font-semibold">Still have a question?</h2>
                        <p className="text-slate mt-2 text-sm">
                            Our team is happy to help with booking, membership or facility questions.
                        </p>
                        <a
                            href={`mailto:${shared.contact.email}`}
                            className="bg-energetic-500 hover:bg-energetic-600 mt-5 inline-block rounded-card px-5 py-2.5 text-sm font-semibold text-white transition-colors"
                        >
                            Contact us
                        </a>
                    </aside>
                </div>
            </Section>

            <Section className="pb-16">
                <BookingCta />
            </Section>
        </PageLayout>
    );
}
