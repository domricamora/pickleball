import type { ReactNode } from 'react';
import { cx } from '@/lib/format';

interface SectionHeadingProps {
    eyebrow?: string;
    title: string;
    description?: string;
    align?: 'left' | 'center';
    children?: ReactNode;
    /**
     * Heading size. `display` is the oversized hero treatment used for the
     * primary section on each page; `section` is the slightly smaller variant
     * used for supporting sections.
     */
    size?: 'display' | 'section';
    /** Renders `title` in the lime accent for emphasis, as the hero does. */
    accentLastWord?: boolean;
}

/**
 * Consistent section header used across the marketing pages. Renders an h2 so
 * every page keeps a single, correct heading hierarchy.
 */
export default function SectionHeading({
    eyebrow,
    title,
    description,
    align = 'left',
    children,
    size = 'section',
    accentLastWord = false,
}: SectionHeadingProps) {
    const centered = align === 'center';

    return (
        <div className={cx(centered ? 'mx-auto max-w-3xl text-center' : 'max-w-3xl')}>
            {eyebrow && <p className="eyebrow text-lime-accent">{eyebrow}</p>}

            <h2
                className={cx(
                    'font-display text-mist-50 mt-4',
                    size === 'display' ? 'text-4xl sm:text-5xl lg:text-6xl' : 'text-3xl sm:text-4xl lg:text-5xl',
                )}
            >
                {accentLastWord ? <AccentLastWord text={title} /> : title}
            </h2>

            {description && <p className="text-mist-300 mt-5 text-base leading-relaxed sm:text-lg">{description}</p>}

            {children && <div className="mt-7">{children}</div>}
        </div>
    );
}

/**
 * Bolds the final word in the lime accent.
 *
 * The reference design sets the second line of a headline in the accent
 * colour, which is what gives it its rhythm. The title is split on the last
 * space so copy stays a single string in config.
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
