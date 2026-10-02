import { usePage } from '@inertiajs/react';
import {
    BadgeCheck,
    CalendarCheck,
    CircleDot,
    CloudSun,
    Dumbbell,
    Handshake,
    Target,
    Trophy,
    Users,
} from 'lucide-react';
import Hero from '@/components/marketing/Hero';
import StatStrip from '@/components/marketing/StatStrip';
import SplitSection from '@/components/marketing/SplitSection';
import ServiceCard from '@/components/marketing/ServiceCard';
import PriceList from '@/components/marketing/PriceList';
import FeatureTrio from '@/components/marketing/FeatureTrio';
import SectionHeading from '@/components/marketing/SectionHeading';
import BookingCta from '@/components/marketing/BookingCta';
import { ButtonLink } from '@/components/ui/Button';
import Seo from '@/components/seo/Seo';
import PageLayout, { Section } from '@/layouts/PageLayout';
import type { SharedProps } from '@/types';

interface HomeProps {
    hero: { eyebrow: string };
    heroStats: Array<{ value: string; label: string }>;
    courtsSection: { eyebrow: string; title: string; description: string; cta: string };
    rentalsSection: { eyebrow: string; title: string; description: string; cta: string };
    services: Array<{ name: string; price: string; description: string; cta: string }>;
    equipment: {
        title: string;
        description: string;
        cta: string;
        items: Array<[string, string]>;
    };
    experienceEvents: { eyebrow: string; title: string; description: string };
    experiencePillars: Array<[string, string]>;
    bookingSteps: Array<{ title: string; description: string }>;
    features: Array<[string, string]>;
    operatorFeatures: Array<[string, string]>;
    operatorCta: { title: string; description: string; button: string };
    community: { title: string; description: string; items: string[] };
}

/*
 * Icon sets, positionally matched to the config arrays. Config stays free of
 * markup, so the mapping lives here rather than in PHP.
 */
const statIcons = [
    <CalendarCheck size={22} />,
    <CloudSun size={22} />,
    <BadgeCheck size={22} />,
    <CircleDot size={22} />,
];

const serviceIcons = [<Users size={24} />, <Dumbbell size={24} />, <Trophy size={24} />];

const pillarIcons = [<Target size={24} />, <Handshake size={24} />, <Trophy size={24} />];

export default function Home(props: HomeProps) {
    const {
        hero,
        heroStats,
        courtsSection,
        rentalsSection,
        services,
        equipment,
        experienceEvents,
        experiencePillars,
        bookingSteps,
        features,
        operatorFeatures,
        operatorCta,
        community,
    } = props;

    const shared = usePage<SharedProps>().props;

    return (
        <PageLayout shared={shared}>
            {/* Title, description and JSON-LD come from the server `seo` prop. */}
            <Seo />

            <Hero
                eyebrow={hero.eyebrow}
                headline={shared.brand.headline}
                description={shared.brand.description}
                coverage={shared.coverage}
            />

            <StatStrip
                stats={heroStats.map((stat, index) => ({
                    icon: statIcons[index] ?? <CircleDot size={22} />,
                    value: stat.value,
                    label: stat.label,
                }))}
            />

            {/* The courts */}
            <Section className="py-20 sm:py-28">
                <SplitSection
                    image="/media/courts-detail.webp"
                    alt="Three blue and green pickleball courts seen from the corner of the hall"
                    width={1600}
                    height={1100}
                    eyebrow={courtsSection.eyebrow}
                    title={courtsSection.title}
                    description={courtsSection.description}
                    ctaLabel={courtsSection.cta}
                    ctaHref="/courts"
                    imageLeft
                />
            </Section>

            {/* Ways to play — the three service cards */}
            <Section className="pb-20 sm:pb-28">
                <SectionHeading
                    eyebrow="Ways to play"
                    title="Three ways to get on court"
                    description="Drop in with other players, book a court for your own group, or put on an event. Every option runs through the same booking flow."
                    align="center"
                />

                <div className="mt-14 grid gap-5 lg:grid-cols-3">
                    {services.map((service, index) => (
                        <ServiceCard
                            key={service.name}
                            service={{
                                icon: serviceIcons[index] ?? <Users size={24} />,
                                name: service.name,
                                price: service.price,
                                description: service.description,
                                ctaLabel: service.cta,
                                ctaHref: '/book',
                            }}
                        />
                    ))}
                </div>
            </Section>

            {/* Court rentals */}
            <Section className="pb-20 sm:pb-28">
                <SplitSection
                    image="/media/courts-aerial.webp"
                    alt="Eight pickleball courts laid out in two rows, seen from above"
                    width={1600}
                    height={900}
                    eyebrow={rentalsSection.eyebrow}
                    title={rentalsSection.title}
                    description={rentalsSection.description}
                    ctaLabel={rentalsSection.cta}
                    ctaHref="/book"
                />
            </Section>

            {/* Book in seconds */}
            <Section className="pb-20 sm:pb-28">
                <div className="bg-night-850 border-hairline-dark rounded-panel border p-8 sm:p-12">
                    <SectionHeading
                        eyebrow="Book in seconds"
                        title="Three steps and you are on court"
                        description="No forms to fight with, no calls to make. The whole booking takes about a minute."
                    />

                    <ol className="mt-12 grid gap-8 md:grid-cols-3">
                        {bookingSteps.map((step, index) => (
                            <li key={step.title}>
                                <span className="font-display text-lime-accent text-4xl">
                                    {String(index + 1).padStart(2, '0')}
                                </span>
                                <h3 className="font-display-plain text-mist-50 mt-3 text-lg tracking-wide">
                                    {step.title}
                                </h3>
                                <p className="text-mist-400 mt-2 text-sm leading-relaxed">{step.description}</p>
                            </li>
                        ))}
                    </ol>
                </div>
            </Section>

            {/* Equipment rentals, with the price list panel */}
            <Section className="pb-20 sm:pb-28">
                <SplitSection
                    image="/media/courts-gear.webp"
                    alt="A rack of pickleball paddles stacked beside a court fence"
                    width={1400}
                    height={1000}
                    eyebrow="Equipment rentals"
                    title="Gear up for great games."
                    description={equipment.description}
                    ctaLabel={equipment.cta}
                    ctaHref="/facilities"
                    imageLeft
                    aside={
                        <PriceList
                            title="Rental rates"
                            items={equipment.items.map(([name, price]) => ({
                                icon: <CircleDot size={15} />,
                                name,
                                price,
                            }))}
                        />
                    }
                />
            </Section>

            {/* Experience events */}
            <Section className="pb-20 sm:pb-28">
                <SectionHeading
                    eyebrow={experienceEvents.eyebrow}
                    title={experienceEvents.title}
                    description={experienceEvents.description}
                    align="center"
                />

                <FeatureTrio
                    className="mt-14"
                    pillars={experiencePillars.map(([title, description], index) => ({
                        icon: pillarIcons[index] ?? <Target size={24} />,
                        title,
                        description,
                    }))}
                />

                <div className="mt-14 flex flex-wrap justify-center gap-3">
                    <ButtonLink href="/events" variant="pill" arrow>
                        Browse events
                    </ButtonLink>
                    <ButtonLink href="/tournaments" variant="outline" arrow>
                        See tournaments
                    </ButtonLink>
                </div>
            </Section>

            {/* Everything the game needs */}
            <Section className="pb-20 sm:pb-28">
                <SectionHeading
                    eyebrow="More than court rental"
                    title="Everything the game needs, in one place"
                    align="center"
                />
                <div className="mt-14 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    {features.map(([title, description]) => (
                        <FeatureCard key={title} title={title} description={description} />
                    ))}
                </div>
            </Section>

            {/* Built for court owners */}
            <Section className="pb-20 sm:pb-28">
                <div className="bg-night-850 border-hairline-dark rounded-panel border p-8 sm:p-12">
                    <div className="max-w-3xl">
                        <p className="eyebrow text-lime-accent">Built for court owners</p>
                        <h2 className="font-display text-mist-50 mt-4 text-3xl sm:text-4xl lg:text-5xl">
                            {operatorCta.title}
                        </h2>
                        <p className="text-mist-300 mt-5 text-lg leading-relaxed">{operatorCta.description}</p>
                        <ButtonLink href="/contact" variant="pill" arrow className="mt-8">
                            Talk to us
                        </ButtonLink>
                    </div>

                    <dl className="border-hairline-dark mt-12 grid gap-x-10 gap-y-6 border-t pt-10 sm:grid-cols-2 lg:grid-cols-4">
                        {operatorFeatures.map(([label, description]) => (
                            <div key={label}>
                                <dt className="text-lime-accent font-semibold">{label}</dt>
                                <dd className="text-mist-400 mt-1.5 text-sm">{description}</dd>
                            </div>
                        ))}
                    </dl>
                </div>
            </Section>

            {/* Community */}
            <Section className="pb-20 sm:pb-28">
                <SectionHeading
                    eyebrow="Community"
                    title={community.title}
                    description={community.description}
                    align="center"
                />
                <ul className="mt-10 flex flex-wrap justify-center gap-3">
                    {community.items.map((item) => (
                        <li key={item} className="bg-night-850 text-mist-200 rounded-pill px-5 py-2.5 text-sm">
                            {item}
                        </li>
                    ))}
                </ul>
            </Section>

            <BookingCta />
        </PageLayout>
    );
}

function FeatureCard({ title, description }: { title: string; description: string }) {
    return (
        <article className="bg-night-850 border-hairline-dark hover:border-night-600 rounded-card border p-6 transition-colors">
            <h3 className="font-display-plain text-mist-50 tracking-wide uppercase">{title}</h3>
            <p className="text-mist-400 mt-2.5 text-sm leading-relaxed">{description}</p>
        </article>
    );
}
