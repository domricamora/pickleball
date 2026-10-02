import { Check } from 'lucide-react';
import { ButtonLink } from '@/components/ui/Button';
import { peso } from '@/lib/format';
import { cx } from '@/lib/format';

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
 *
 * The highlighted tier is the one filled with the lime accent — the same
 * inversion the reference design uses for its "most popular" card.
 */
export default function PricingCard({ tier }: PricingCardProps) {
    const highlighted = Boolean(tier.highlighted);

    return (
        <article
            className={cx(
                'flex h-full flex-col rounded-panel p-7',
                highlighted
                    ? 'bg-lime-accent text-night-950 shadow-[0_24px_60px_-24px_rgba(183,227,74,0.45)]'
                    : 'bg-night-850 border-hairline-dark border',
            )}
        >
            {tier.badge && (
                <span
                    className={cx(
                        'eyebrow rounded-pill px-3 py-1',
                        highlighted ? 'bg-night-950 text-lime-accent' : 'bg-night-800 text-lime-accent',
                    )}
                >
                    {tier.badge}
                </span>
            )}

            <h3
                className={cx(
                    'font-display-plain mt-5 text-xl tracking-wide',
                    highlighted ? 'text-night-950' : 'text-mist-50',
                )}
            >
                {tier.name}
            </h3>

            <p className={cx('mt-2 min-h-[3rem] text-sm', highlighted ? 'text-night-800' : 'text-mist-400')}>
                {tier.description}
            </p>

            <p className="mt-5">
                {tier.price === null ? (
                    <span
                        className={cx('font-display-plain text-3xl', highlighted ? 'text-night-950' : 'text-mist-50')}
                    >
                        Custom
                    </span>
                ) : (
                    <>
                        <span
                            className={cx(
                                'font-display-plain text-4xl',
                                highlighted ? 'text-night-950' : 'text-mist-50',
                            )}
                        >
                            {peso(tier.price, { decimals: false })}
                        </span>
                        <span className={cx('text-sm', highlighted ? 'text-night-800' : 'text-mist-400')}>
                            {' '}
                            / {tier.cadence}
                        </span>
                    </>
                )}
            </p>

            <ul className={cx('mt-6 flex-1 space-y-3 text-sm', highlighted ? 'text-night-900' : 'text-mist-300')}>
                {tier.features.map((feature) => (
                    <li key={feature} className="flex items-start gap-2.5">
                        <Check
                            size={17}
                            aria-hidden
                            className={cx('mt-0.5 shrink-0', highlighted ? 'text-night-950' : 'text-lime-accent')}
                        />
                        <span>{feature}</span>
                    </li>
                ))}
            </ul>

            <div className="mt-7">
                <ButtonLink href="/book" variant={highlighted ? 'pill' : 'outline'} arrow className="w-full">
                    Get started
                </ButtonLink>
            </div>
        </article>
    );
}
