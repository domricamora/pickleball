import { ButtonLink } from '@/components/ui/Button';
import { assetUrl } from '@/lib/format';

interface BookingCtaProps {
    title?: string;
    description?: string;
    primaryLabel?: string;
    primaryHref?: string;
    secondaryLabel?: string;
    secondaryHref?: string;
    /**
     * Background photograph. Defaults to the closing banner image; pass one of
     * the other credited crops to vary it per page.
     */
    image?: string;
    alt?: string;
}

/**
 * The platform's primary call to action (plan.md §4). Reused on the home page
 * and on long-form marketing pages where the reader needs a next step.
 */
export default function BookingCta({
    title = 'Ready to play?',
    description = 'Your next game is a booking away.',
    primaryLabel = 'Book a court',
    primaryHref = '/book',
    secondaryLabel = 'View memberships',
    secondaryHref = '/memberships',
    image = '/media/courts-row.webp',
    alt = 'Pickleball courts beside a floodlit fence at the end of the day',
}: BookingCtaProps) {
    return (
        <section className="cinema cinema-grain bg-night-950 relative isolate flex min-h-[24rem] items-center overflow-hidden">
            <img src={assetUrl(image)} alt={alt} width={2000} height={1125} loading="lazy" decoding="async" />

            <div className="mx-auto w-full max-w-7xl px-4 py-20 sm:px-6 sm:py-24 lg:px-8">
                <p className="eyebrow text-lime-accent">Book in seconds</p>
                <h2 className="font-display text-mist-50 mt-4 max-w-2xl text-4xl sm:text-5xl lg:text-6xl">{title}</h2>
                <p className="text-mist-300 mt-5 max-w-xl text-lg leading-relaxed">{description}</p>

                <div className="mt-9 flex flex-col gap-3 sm:flex-row sm:items-center">
                    <ButtonLink href={primaryHref} variant="pill" arrow className="w-full sm:w-auto">
                        {primaryLabel}
                    </ButtonLink>
                    <ButtonLink href={secondaryHref} variant="outline" arrow className="w-full sm:w-auto">
                        {secondaryLabel}
                    </ButtonLink>
                </div>
            </div>
        </section>
    );
}
