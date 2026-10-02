import type { ReactNode } from 'react';
import { ButtonLink } from '@/components/ui/Button';
import { cx, assetUrl } from '@/lib/format';

interface SplitSectionProps {
    /** Photo in `public/media`, credited in resources/media/media-credits.md. */
    image: string;
    alt: string;
    /** Intrinsic width/height of the source crop, to reserve layout space. */
    width: number;
    height: number;
    eyebrow: string;
    title: string;
    description: string;
    ctaLabel: string;
    ctaHref: string;
    /** `imageLeft` alternates the layout down the page. */
    imageLeft?: boolean;
    /** Optional panel rendered beside the copy, e.g. the equipment price list. */
    aside?: ReactNode;
}

/**
 * The alternating photo-and-copy block that makes up the body of the
 * marketing page.
 *
 * The image is decorative-supporting rather than decorative: it shows the kind
 * of court being sold, so it carries real alt text.
 */
export default function SplitSection({
    image,
    alt,
    width,
    height,
    eyebrow,
    title,
    description,
    ctaLabel,
    ctaHref,
    imageLeft = false,
    aside,
}: SplitSectionProps) {
    const photo = (
        <div className="photo-wash rounded-card">
            <img
                src={assetUrl(image)}
                alt={alt}
                width={width}
                height={height}
                loading="lazy"
                decoding="async"
                className="aspect-4/3 w-full object-cover sm:aspect-16/10 lg:aspect-4/3"
            />
        </div>
    );

    const copy = (
        <div className={cx('lg:max-w-xl', imageLeft && 'lg:ml-auto')}>
            <p className="eyebrow text-lime-accent">{eyebrow}</p>
            <h2 className="font-display text-mist-50 mt-4 text-3xl sm:text-4xl lg:text-5xl">{title}</h2>
            <p className="text-mist-300 mt-5 text-base leading-relaxed sm:text-lg">{description}</p>
            <ButtonLink href={ctaHref} variant="outline" arrow className="mt-8">
                {ctaLabel}
            </ButtonLink>

            {aside && <div className="mt-10">{aside}</div>}
        </div>
    );

    return (
        <div className="grid items-center gap-10 lg:grid-cols-2 lg:gap-16">
            {imageLeft ? (
                <>
                    {photo}
                    {copy}
                </>
            ) : (
                <>
                    {copy}
                    {photo}
                </>
            )}
        </div>
    );
}
