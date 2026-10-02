import { Link } from '@inertiajs/react';
import { ArrowUpRight } from 'lucide-react';
import type { ButtonHTMLAttributes, ReactNode } from 'react';
import { cx, withBasePath } from '@/lib/format';

type Variant = 'primary' | 'accent' | 'ghost' | 'pill' | 'outline';

/*
 * The admin and booking surfaces stay light (plan.md §3), so `primary`,
 * `accent` and `ghost` keep their original light-theme styling and are used
 * there. `pill` and `outline` are the dark marketing-site actions.
 */
const variants: Record<Variant, string> = {
    primary: 'bg-pickle-500 text-white hover:bg-pickle-600',
    accent: 'bg-energetic-500 text-white hover:bg-energetic-600',
    ghost: 'bg-white text-pickle-800 border border-hairline hover:bg-pickle-50',
    pill: 'btn-pill btn-pill-primary px-7 py-3 text-[0.8125rem]',
    outline: 'btn-pill btn-pill-outline px-7 py-3 text-[0.8125rem]',
};

interface ButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
    variant?: Variant;
    children: ReactNode;
}

/**
 * Shared call-to-action button. Orange (`accent`) is reserved for primary
 * booking actions so it keeps its meaning — see plan.md §3.
 */
export default function Button({ variant = 'primary', children, className = '', ...props }: ButtonProps) {
    return (
        <button
            className={cx(
                'rounded-card px-6 py-3 font-semibold transition-colors disabled:cursor-not-allowed disabled:opacity-60',
                // The pill variants supply their own radius and padding.
                variant === 'pill' || variant === 'outline' ? '' : 'rounded-card',
                variants[variant],
                className,
            )}
            {...props}
        >
            {children}
        </button>
    );
}

interface ButtonLinkProps {
    href: string;
    variant?: Variant;
    children: ReactNode;
    className?: string;
    /** Renders the trailing arrow glyph used by every marketing CTA. */
    arrow?: boolean;
}

export function ButtonLink({ href, variant = 'primary', children, className = '', arrow = false }: ButtonLinkProps) {
    return (
        <Link
            href={withBasePath(href)}
            className={cx(
                'rounded-card px-6 py-3 font-semibold transition-colors',
                variant === 'pill' || variant === 'outline' ? '' : 'rounded-card',
                variants[variant],
                className,
            )}
        >
            {children}
            {arrow && <ArrowUpRight size={16} strokeWidth={2.5} aria-hidden className="ml-1.5 inline" />}
        </Link>
    );
}
