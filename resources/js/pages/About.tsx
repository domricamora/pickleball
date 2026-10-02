import { usePage } from '@inertiajs/react';
import SectionHeading from '@/components/marketing/SectionHeading';
import BookingCta from '@/components/marketing/BookingCta';
import Seo from '@/components/seo/Seo';
import PageLayout, { Section } from '@/layouts/PageLayout';
import type { SharedProps } from '@/types';

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

            <Section className="py-14">
                <div className="grid gap-12 lg:grid-cols-2 lg:items-center">
                    <div>
                        <span className="bg-pickle-50 text-pickle-700 rounded-full px-4 py-1.5 text-xs font-bold tracking-wide uppercase">
                            About us
                        </span>
                        <h1 className="font-display mt-5 text-4xl font-extrabold text-pickle-900 sm:text-5xl">
                            {about.mission_title}
                        </h1>
                        <p className="text-slate mt-6 text-lg">{about.mission_body}</p>
                    </div>

                    <div className="bg-pickle-900 rounded-panel relative overflow-hidden p-8 text-white">
                        <div
                            className="pointer-events-none absolute inset-0 opacity-20"
                            style={{
                                backgroundImage:
                                    'linear-gradient(to right, rgba(255,255,255,.16) 1px, transparent 1px), linear-gradient(to bottom, rgba(255,255,255,.16) 1px, transparent 1px)',
                                backgroundSize: '44px 44px',
                            }}
                            aria-hidden
                        />
                        <p className="text-lime-accent relative text-sm font-bold tracking-widest uppercase">
                            {shared.brand.tagline}
                        </p>
                        <p className="relative mt-4 text-3xl font-extrabold">
                            Built for Philippine players, from beginners to league regulars.
                        </p>
                    </div>
                </div>
            </Section>

            <Section className="pb-16">
                <SectionHeading eyebrow="What guides us" title="Four ideas behind every screen" align="center" />
                <div className="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    {about.values.map(([label, description]) => (
                        <article key={label} className="border-hairline rounded-panel border bg-white p-7">
                            <h2 className="font-display text-2xl font-extrabold text-pickle-600">{label}</h2>
                            <p className="text-slate mt-3 text-sm">{description}</p>
                        </article>
                    ))}
                </div>
            </Section>

            <Section className="pb-16">
                <BookingCta />
            </Section>
        </PageLayout>
    );
}
