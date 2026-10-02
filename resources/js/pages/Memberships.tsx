import { usePage } from '@inertiajs/react';
import PricingCard, { type PricingTier } from '@/components/marketing/PricingCard';
import SectionHeading from '@/components/marketing/SectionHeading';
import BookingCta from '@/components/marketing/BookingCta';
import Seo from '@/components/seo/Seo';
import PageLayout, { Section } from '@/layouts/PageLayout';
import type { SharedProps } from '@/types';

interface MembershipsProps {
    content: {
        title: string;
        description: string;
        benefits: Array<[string, string]>;
    };
    packages: Array<[string, string]>;
    tiers: PricingTier[];
}

export default function Memberships({ content, packages, tiers }: MembershipsProps) {
    const shared = usePage<SharedProps>().props;

    return (
        <PageLayout shared={shared}>
            <Seo />

            <Section className="py-14">
                <SectionHeading
                    eyebrow="Memberships"
                    title={content.title}
                    description={content.description}
                    align="center"
                />

                <div className="mt-12 grid gap-6 lg:grid-cols-3">
                    {tiers.map((tier) => (
                        <PricingCard key={tier.name} tier={tier} />
                    ))}
                </div>
            </Section>

            <Section className="pb-16">
                <h2 className="font-display text-2xl font-extrabold text-pickle-900 sm:text-3xl">
                    What every member gets
                </h2>
                <dl className="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    {content.benefits.map(([label, description]) => (
                        <div key={label} className="border-hairline rounded-card border bg-white p-6">
                            <dt className="text-pickle-800 font-semibold">{label}</dt>
                            <dd className="text-slate mt-2 text-sm">{description}</dd>
                        </div>
                    ))}
                </dl>
            </Section>

            <Section className="pb-16">
                <h2 className="font-display text-2xl font-extrabold text-pickle-900 sm:text-3xl">Session packages</h2>
                <div className="mt-8 grid gap-5 sm:grid-cols-3">
                    {packages.map(([label, description]) => (
                        <article key={label} className="border-hairline rounded-card border bg-white p-6">
                            <h3 className="text-pickle-800 font-semibold">{label}</h3>
                            <p className="text-slate mt-2 text-sm">{description}</p>
                        </article>
                    ))}
                </div>
                <p className="text-slate mt-6 text-sm">
                    Package usage and expiry are tracked per purchase, so included sessions are always clear.
                </p>
            </Section>

            <Section className="pb-16">
                <BookingCta
                    title="Join the community"
                    description="Start with a drop-in, or join a plan that fits how often you play."
                />
            </Section>
        </PageLayout>
    );
}
