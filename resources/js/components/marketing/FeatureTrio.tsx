import type { ReactNode } from 'react';
import { cx } from '@/lib/format';

export interface Pillar {
    icon: ReactNode;
    title: string;
    description: string;
}

interface FeatureTrioProps {
    pillars: Pillar[];
    className?: string;
}

/**
 * The three icon-led pillars beneath the events band in the reference design.
 * Separated by vertical rules rather than boxed, which keeps the section light.
 */
export default function FeatureTrio({ pillars, className = '' }: FeatureTrioProps) {
    return (
        <dl className={cx('grid gap-8 sm:grid-cols-3 sm:gap-6', className)}>
            {pillars.map((pillar) => (
                <div
                    key={pillar.title}
                    className="sm:border-hairline-dark sm:border-l sm:pl-6 sm:first:border-l-0 sm:first:pl-0"
                >
                    <span className="text-lime-accent" aria-hidden>
                        {pillar.icon}
                    </span>
                    <dt className="font-display-plain text-mist-50 mt-4 tracking-wide uppercase">{pillar.title}</dt>
                    <dd className="text-mist-400 mt-2 text-sm leading-relaxed">{pillar.description}</dd>
                </div>
            ))}
        </dl>
    );
}
