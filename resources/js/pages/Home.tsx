import { Link, usePage } from '@inertiajs/react';
import Hero from '@/components/marketing/Hero';
import SectionHeading from '@/components/marketing/SectionHeading';
import BookingCta from '@/components/marketing/BookingCta';
import Seo from '@/components/seo/Seo';
import PageLayout, { Section } from '@/layouts/PageLayout';
import type { SharedProps } from '@/types';

interface HomeProps {
    findYourGame: { title: string; description: string; filters: Record<string, string> };
    bookingSteps: Array<{ title: string; description: string }>;
    features: Array<[string, string]>;
    operatorFeatures: Array<[string, string]>;
    operatorCta: { title: string; description: string; button: string };
    community: { title: string; description: string; items: string[] };
}

export default function Home({
    findYourGame,
    bookingSteps,
    features,
    operatorFeatures,
    operatorCta,
    community,
}: HomeProps) {
    const shared = usePage<SharedProps>().props;

    return (
        <PageLayout shared={shared}>
            {/* Title, description and JSON-LD come from the server `seo` prop. */}
            <Seo />

            <Hero
                headline={shared.brand.headline}
                tagline={shared.brand.tagline}
                description={shared.brand.description}
                coverage={shared.coverage}
            />

            {/* Find Your Game */}
            <Section className="py-16 sm:py-20">
                <SectionHeading
                    eyebrow="Find your game"
                    title={findYourGame.title}
                    description={findYourGame.description}
                    align="center"
                />
                <div className="mt-8 flex flex-wrap justify-center gap-3">
                    {Object.values(findYourGame.filters).map((label) => (
                        <span
                            key={label}
                            className="border-hairline rounded-full border bg-white px-5 py-2.5 text-sm font-medium text-slate"
                        >
                            {label}
                        </span>
                    ))}
                </div>
                <div className="mt-8 flex justify-center">
                    <Link
                        href="/facilities"
                        className="border-hairline text-pickle-800 hover:bg-pickle-50 rounded-card border bg-white px-6 py-3 font-semibold transition-colors"
                    >
                        Browse facilities
                    </Link>
                </div>
            </Section>

            {/* Book in Seconds */}
            <Section className="pb-16 sm:pb-20">
                <SectionHeading
                    eyebrow="Book in seconds"
                    title="Three steps and you are on court"
                    description="No forms to fight with, no calls to make. The whole booking takes about a minute."
                    align="center"
                />
                <ol className="mt-10 grid gap-5 md:grid-cols-3">
                    {bookingSteps.map((step, index) => (
                        <li key={step.title} className="border-hairline rounded-panel border bg-white p-7">
                            <span className="bg-pickle-500 grid h-11 w-11 place-items-center rounded-card text-lg font-extrabold text-white">
                                {index + 1}
                            </span>
                            <h3 className="font-display mt-5 text-xl font-bold text-pickle-900">{step.title}</h3>
                            <p className="text-slate mt-2 text-sm">{step.description}</p>
                        </li>
                    ))}
                </ol>
            </Section>

            {/* More Than Court Rental */}
            <Section className="pb-16 sm:pb-20">
                <SectionHeading
                    eyebrow="More than court rental"
                    title="Everything the game needs, in one place"
                    align="center"
                />
                <div className="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {features.map(([title, description]) => (
                        <FeatureCard key={title} title={title} description={description} />
                    ))}
                </div>
            </Section>

            {/* Built for Court Owners */}
            <Section className="pb-16 sm:pb-20">
                <div className="bg-pickle-900 rounded-panel overflow-hidden px-6 py-14 text-white sm:px-12">
                    <div className="max-w-3xl">
                        <span className="bg-pickle-800 text-lime-accent rounded-full px-4 py-1.5 text-xs font-bold tracking-wide uppercase">
                            Built for court owners
                        </span>
                        <h2 className="font-display mt-4 text-3xl font-extrabold sm:text-4xl">{operatorCta.title}</h2>
                        <p className="mt-4 text-lg text-pickle-100">{operatorCta.description}</p>
                        <Link
                            href="/contact"
                            className="bg-energetic-500 hover:bg-energetic-600 mt-6 inline-block rounded-card px-7 py-3.5 font-semibold text-white transition-colors"
                        >
                            {operatorCta.button}
                        </Link>
                    </div>

                    <dl className="mt-12 grid gap-x-10 gap-y-6 sm:grid-cols-2 lg:grid-cols-4">
                        {operatorFeatures.map(([label, description]) => (
                            <div key={label}>
                                <dt className="text-lime-accent font-semibold">{label}</dt>
                                <dd className="mt-1.5 text-sm text-pickle-100">{description}</dd>
                            </div>
                        ))}
                    </dl>
                </div>
            </Section>

            {/* Community */}
            <Section className="pb-16 sm:pb-20">
                <SectionHeading
                    eyebrow="Community"
                    title={community.title}
                    description={community.description}
                    align="center"
                />
                <ul className="mt-8 flex flex-wrap justify-center gap-3">
                    {community.items.map((item) => (
                        <li
                            key={item}
                            className="bg-pickle-50 text-pickle-800 rounded-full px-5 py-2.5 text-sm font-semibold"
                        >
                            {item}
                        </li>
                    ))}
                </ul>
            </Section>

            <Section className="pb-16 sm:pb-20">
                <BookingCta />
            </Section>
        </PageLayout>
    );
}

function FeatureCard({ title, description }: { title: string; description: string }) {
    return (
        <article className="border-hairline rounded-card border bg-white p-6">
            <h3 className="text-pickle-800 font-semibold">{title}</h3>
            <p className="text-slate mt-2 text-sm">{description}</p>
        </article>
    );
}
