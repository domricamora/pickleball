import { usePage } from '@inertiajs/react';
import SectionHeading from '@/components/marketing/SectionHeading';
import BookingCta from '@/components/marketing/BookingCta';
import Seo from '@/components/seo/Seo';
import PageLayout, { Section } from '@/layouts/PageLayout';
import type { SharedProps } from '@/types';
import { assetUrl } from '@/lib/format';

interface AboutProps {
    about: {
        mission_title: string;
        mission_body: string;
        values: Array<[string, string]>;
    };
}

export default function About({ about }: AboutProps) {
    const shared = usePage<SharedProps>().props;

    return (
        <PageLayout shared={shared}>
            <Seo />

            <Section className="py-20 sm:py-28">
                <div className="grid items-center gap-12 lg:grid-cols-2 lg:gap-16">
                    <div>
                        <p className="eyebrow text-lime-accent">About us</p>
                        <h1 className="font-display text-mist-50 mt-5 text-4xl sm:text-5xl lg:text-6xl">
                            {about.mission_title}
                        </h1>
                        <p className="text-mist-300 mt-7 text-lg leading-relaxed">{about.mission_body}</p>
                    </div>

                    <div className="photo-wash rounded-panel">
                        <img
                            src={assetUrl('/media/courts-outdoor.webp')}
                            alt="Two outdoor pickleball courts in afternoon light"
                            width={1400}
                            height={1000}
                            loading="lazy"
                            decoding="async"
                            className="aspect-4/3 w-full object-cover"
                        />
                    </div>
                </div>
            </Section>

            <Section className="pb-20 sm:pb-28">
                <div className="bg-night-850 border-hairline-dark rounded-panel border p-8 sm:p-12">
                    <p className="eyebrow text-lime-accent">{shared.brand.tagline}</p>
                    <p className="font-display-plain text-mist-50 mt-5 text-2xl leading-snug sm:text-3xl">
                        Built for Philippine players, from beginners to league regulars.
                    </p>
                </div>
            </Section>

            <Section className="pb-20 sm:pb-28">
                <SectionHeading eyebrow="What guides us" title="Four ideas behind every screen" align="center" />
                <div className="mt-14 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    {about.values.map(([label, description]) => (
                        <article key={label} className="bg-night-850 border-hairline-dark rounded-panel border p-7">
                            <h2 className="font-display text-lime-accent text-3xl">{label}</h2>
                            <p className="text-mist-400 mt-3 text-sm leading-relaxed">{description}</p>
                        </article>
                    ))}
                </div>
            </Section>

            <Section className="pb-20 sm:pb-28">
                <BookingCta />
            </Section>
        </PageLayout>
    );
}
