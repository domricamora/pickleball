import { usePage } from '@inertiajs/react';
import SectionHeading from '@/components/marketing/SectionHeading';
import BookingCta from '@/components/marketing/BookingCta';
import Seo from '@/components/seo/Seo';
import PageLayout, { EmptyState, Section } from '@/layouts/PageLayout';
import type { SharedProps } from '@/types';

interface BlogProps {
    posts: unknown[];
}

export default function Blog({ posts }: BlogProps) {
    const shared = usePage<SharedProps>().props;

    return (
        <PageLayout shared={shared}>
            <Seo />

            <Section className="py-14">
                <SectionHeading
                    eyebrow="Journal"
                    title="Play better, one article at a time"
                    description="Guides, court news and community stories from the Philippine pickleball scene."
                />
            </Section>

            <Section className="pb-16">
                {posts.length === 0 ? (
                    <EmptyState
                        title="No posts published yet"
                        description="We are writing our first guides. Check back soon, or explore courts and pricing in the meantime."
                        actionHref="/facilities"
                        actionLabel="Browse facilities"
                    />
                ) : (
                    <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                        {posts.map((post) => (
                            <article key={String(post)} className="border-hairline rounded-card border bg-white p-6">
                                {String(post)}
                            </article>
                        ))}
                    </div>
                )}
            </Section>

            <Section className="pb-16">
                <BookingCta />
            </Section>
        </PageLayout>
    );
}
