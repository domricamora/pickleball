import { Check } from 'lucide-react';
import { ButtonLink } from '@/components/ui/Button';
import { peso } from '@/lib/format';

export interface PricingTier {
    name: string;
    price: number | null;
    cadence: string;
    description: string;
    features: string[];
    highlighted?: boolean;
    badge?: string;
}

interface PricingCardProps {
    tier: PricingTier;
}

/**
 * Membership / pricing tier card. Prices are peso amounts formatted at
 * render time (plan.md §5, §32).
 */
export default function PricingCard({ tier }: PricingCardProps) {
    return (
        <article
            className={`flex h-full flex-col rounded-panel p-7 ${
                tier.highlighted ? 'bg-pickle-900 text-white shadow-lg' : 'border-hairline border bg-white'
            }`}
        >
            {tier.badge && (
                <span
                    className={`rounded-full px-3 py-1 text-xs font-bold uppercase ${
                        tier.highlighted ? 'bg-lime-accent text-pickle-900' : 'bg-pickle-50 text-pickle-700'
                    }`}
                >
                    {tier.badge}
                </span>
            )}

            <h3
                className={`font-display text-2xl font-extrabold ${tier.highlighted ? 'text-white' : 'text-pickle-900'}`}
            >
                {tier.name}
            </h3>

            <p className={`mt-2 min-h-[3rem] text-sm ${tier.highlighted ? 'text-pickle-100' : 'text-slate'}`}>
                {tier.description}
            </p>

            <p className="mt-5">
                {tier.price === null ? (
                    <span
                        className={`font-display text-3xl font-extrabold ${tier.highlighted ? 'text-white' : 'text-pickle-700'}`}
                    >
                        Custom
                    </span>
                ) : (
                    <>
                        <span
                            className={`font-display text-4xl font-extrabold ${
                                tier.highlighted ? 'text-white' : 'text-pickle-700'
                            }`}
                        >
                            {peso(tier.price, { decimals: false })}
                        </span>
                        <span className={tier.highlighted ? 'text-pickle-200' : 'text-slate'}> / {tier.cadence}</span>
                    </>
                )}
            </p>

            <ul className={`mt-6 flex-1 space-y-3 text-sm ${tier.highlighted ? 'text-pickle-100' : 'text-slate'}`}>
                {tier.features.map((feature) => (
                    <li key={feature} className="flex items-start gap-2.5">
                        <Check
                            size={17}
                            aria-hidden
                            className={`mt-0.5 shrink-0 ${tier.highlighted ? 'text-lime-accent' : 'text-pickle-500'}`}
                        />
                        <span>{feature}</span>
                    </li>
                ))}
            </ul>

            <div className="mt-7">
                <ButtonLink href="/book" variant={tier.highlighted ? 'accent' : 'primary'} className="w-full">
                    Get Started
                </ButtonLink>
            </div>
        </article>
    );
}
