import { ButtonLink } from '@/components/ui/Button';
import BookingWidget from '@/components/marketing/BookingWidget';
import { MapPin } from 'lucide-react';
import { assetUrl } from '@/lib/format';

interface HeroProps {
    eyebrow: string;
    headline: string;
    description: string;
    coverage: string[];
}

/**
 * Homepage hero (plan.md §4).
 *
 * The photograph is the hero's real content, so it carries alt text; the lime
 * panel on the right is a working availability lookup rather than decoration.
 */
export default function Hero({ eyebrow, headline, description, coverage }: HeroProps) {
    return (
        <section className="cinema cinema-grain bg-night-950 relative isolate flex min-h-[38rem] items-center overflow-hidden">
            {/*
              The <img> is the poster and the fallback; the <video> paints over
              it only once it is actually playing. Ordering them this way means
              reduced-motion users, autoplay-blocked browsers and anyone still
              on the poster all see a photograph rather than an empty hero --
              a background <video> with no poster renders a black box.

              autoPlay/muted/playsInline are all required: browsers block
              autoplay on a video with sound or without the muted attribute.
            */}
            <img
                src={assetUrl('/media/hero-poster.webp')}
                alt="A row of outdoor pickleball courts behind black perimeter fencing"
                width={2000}
                height={1125}
                fetchPriority="high"
                decoding="async"
            />

            <video
                className="absolute inset-0 h-full w-full object-cover"
                autoPlay
                muted
                loop
                playsInline
                // Decorative: the poster above carries the same description.
                aria-hidden="true"
                tabIndex={-1}
                poster={assetUrl('/media/hero-poster.webp')}
            >
                <source src={assetUrl('/media/hero-loop.mp4')} type="video/mp4" />
            </video>

            <div className="mx-auto grid w-full max-w-7xl items-center gap-12 px-4 py-20 sm:px-6 sm:py-24 lg:grid-cols-[1.15fr_0.85fr] lg:gap-16 lg:px-8 lg:py-28">
                <div>
                    <p className="eyebrow text-lime-accent">{eyebrow}</p>

                    <h1 className="font-display text-mist-50 mt-6 text-5xl sm:text-6xl lg:text-7xl xl:text-[5.25rem]">
                        <AccentLastWord text={headline} />
                    </h1>

                    <p className="text-mist-200 mt-7 max-w-xl text-lg leading-relaxed">{description}</p>

                    <div className="mt-10 flex flex-col gap-3 sm:flex-row sm:items-center">
                        <ButtonLink href="/book" variant="pill" arrow className="w-full sm:w-auto">
                            Book a court
                        </ButtonLink>
                        <ButtonLink href="/facilities" variant="outline" arrow className="w-full sm:w-auto">
                            Explore the club
                        </ButtonLink>
                    </div>

                    {coverage.length > 0 && (
                        <p className="text-mist-400 mt-9 flex items-center gap-2 text-sm">
                            <MapPin size={15} className="text-lime-accent shrink-0" aria-hidden />
                            {coverage.join(' · ')}
                        </p>
                    )}
                </div>

                <div className="lg:justify-self-end">
                    <BookingWidget />
                </div>
            </div>
        </section>
    );
}

/**
 * Sets the final word of the headline in the lime accent.
 *
 * The reference design breaks its hero headline across two lines with the
 * second line accented; splitting on the last space reproduces that rhythm
 * while keeping the headline a single string in config.
 */
function AccentLastWord({ text }: { text: string }) {
    const lastSpace = text.lastIndexOf(' ');

    if (lastSpace === -1) {
        return <span className="text-lime-accent">{text}</span>;
    }

    return (
        <>
            {text.slice(0, lastSpace + 1)}
            <span className="text-lime-accent">{text.slice(lastSpace + 1)}</span>
        </>
    );
}
