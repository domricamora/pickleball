import { usePage } from '@inertiajs/react';
import PricingCard, { type PricingTier } from '@/components/marketing/PricingCard';
import BookingCta from '@/components/marketing/BookingCta';
import Seo from '@/components/seo/Seo';
import PageLayout, { PageHero, Section } from '@/layouts/PageLayout';
import type { SharedProps } from '@/types';

interface PricingProps {
    tiers: PricingTier[];
}

export default function Pricing({ tiers }: PricingProps) {
    const shared = usePage<SharedProps>().props;

    return (
        <PageLayout shared={shared}>
            <Seo />

            <PageHero
                eyebrow="Pricing"
                title="Simple pricing in Philippine pesos"
                description="No hidden fees. What you see is what you pay, and every facility sets its own rates."
                image="/media/courts-detail.webp"
                alt="Pickleball courts in a bright indoor hall"
            />

            <Section className="py-20 sm:py-24">
                <div className="grid gap-6 lg:grid-cols-3">
                    {tiers.map((tier) => (
                        <PricingCard key={tier.name} tier={tier} />
                    ))}
                </div>

                <p className="text-mist-400 mx-auto mt-12 max-w-2xl text-center text-sm leading-relaxed">
                    Prices shown are indicative platform rates. Each facility publishes its own weekday, weekend, peak,
                    off-peak and member pricing, and applicable taxes are applied as configured by that facility.
                </p>
            </Section>

            <Section className="pb-20 sm:pb-24">
                <BookingCta
                    title="Start playing this week"
                    description="No membership required to book your first court."
                />
            </Section>
        </PageLayout>
    );
}
