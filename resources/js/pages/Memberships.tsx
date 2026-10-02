import { usePage } from '@inertiajs/react';
import PricingCard, { type PricingTier } from '@/components/marketing/PricingCard';
import BookingCta from '@/components/marketing/BookingCta';
import Seo from '@/components/seo/Seo';
import PageLayout, { PageHero, Section } from '@/layouts/PageLayout';
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

            <PageHero
                eyebrow="Memberships"
                title={content.title}
                description={content.description}
                image="/media/courts-aerial.webp"
                alt="A row of pickleball courts viewed from above"
            />

            <Section className="py-20 sm:py-24">
                <div className="grid gap-6 lg:grid-cols-3">
                    {tiers.map((tier) => (
                        <PricingCard key={tier.name} tier={tier} />
                    ))}
                </div>
            </Section>

            <Section className="pb-20 sm:pb-24">
                <h2 className="font-display text-mist-50 text-3xl sm:text-4xl">What every member gets</h2>
                <dl className="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    {content.benefits.map(([label, description]) => (
                        <div key={label} className="bg-night-850 border-hairline-dark rounded-card border p-6">
                            <dt className="text-lime-accent font-semibold">{label}</dt>
                            <dd className="text-mist-400 mt-2 text-sm leading-relaxed">{description}</dd>
                        </div>
                    ))}
                </dl>
            </Section>

            <Section className="pb-20 sm:pb-24">
                <h2 className="font-display text-mist-50 text-3xl sm:text-4xl">Session packages</h2>
                <div className="mt-10 grid gap-5 sm:grid-cols-3">
                    {packages.map(([label, description]) => (
                        <article key={label} className="bg-night-850 border-hairline-dark rounded-card border p-6">
                            <h3 className="font-display-plain text-mist-50 tracking-wide uppercase">{label}</h3>
                            <p className="text-mist-400 mt-2 text-sm leading-relaxed">{description}</p>
                        </article>
                    ))}
                </div>
                <p className="text-mist-500 mt-6 text-sm">
                    Package usage and expiry are tracked per purchase, so included sessions are always clear.
                </p>
            </Section>

            <Section className="pb-20 sm:pb-24">
                <BookingCta
                    title="Join the community"
                    description="Start with a drop-in, or join a plan that fits how often you play."
                    secondaryLabel="See pricing"
                    secondaryHref="/pricing"
                />
            </Section>
        </PageLayout>
    );
}
