import type { ReactNode } from 'react';

interface SectionHeadingProps {
    eyebrow?: string;
    title: string;
    description?: string;
    align?: 'left' | 'center';
    children?: ReactNode;
}

/**
 * Consistent section header used across the marketing pages. Renders an
 * h2 so every page keeps a single, correct heading hierarchy.
 */
export default function SectionHeading({ eyebrow, title, description, align = 'left', children }: SectionHeadingProps) {
    const centered = align === 'center';

    return (
        <div className={centered ? 'mx-auto max-w-3xl text-center' : 'max-w-3xl'}>
            {eyebrow && (
                <span className="bg-pickle-50 text-pickle-700 rounded-full px-4 py-1.5 text-xs font-bold tracking-wide uppercase">
                    {eyebrow}
                </span>
            )}
            <h2 className="font-display mt-4 text-3xl font-extrabold text-pickle-900 sm:text-4xl">{title}</h2>
            {description && <p className="text-slate mt-4 text-lg">{description}</p>}
            {children && <div className={centered ? 'mt-6' : 'mt-6'}>{children}</div>}
        </div>
    );
}
