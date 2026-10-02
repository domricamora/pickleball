import type { ReactNode } from 'react';

export interface StatItem {
    icon: ReactNode;
    value: string;
    label: string;
}

interface StatStripProps {
    stats: StatItem[];
}

/**
 * The four-up stat band that sits directly beneath the hero.
 *
 * Every value comes from real platform data passed in by the controller. No
 * figure here is invented — plan.md §33 forbids fabricated numbers on the
 * marketing site, and a rating or member count we cannot substantiate would be
 * a lie told at the top of the page.
 */
export default function StatStrip({ stats }: StatStripProps) {
    return (
        <section className="bg-night-900 border-hairline-dark border-y" aria-label="Platform at a glance">
            <dl className="mx-auto grid w-full max-w-7xl grid-cols-2 divide-x divide-y divide-hairline-dark px-4 sm:px-6 lg:grid-cols-4 lg:divide-y-0 lg:px-8">
                {stats.map((stat) => (
                    <div key={stat.label} className="flex items-center gap-4 px-4 py-6 sm:px-6 sm:py-7">
                        <span className="text-lime-accent shrink-0" aria-hidden>
                            {stat.icon}
                        </span>
                        <div className="min-w-0">
                            <dd className="font-display-plain text-2xl text-mist-50 sm:text-3xl">{stat.value}</dd>
                            <dt className="eyebrow text-mist-400 mt-1">{stat.label}</dt>
                        </div>
                    </div>
                ))}
            </dl>
        </section>
    );
}
