import { Link } from '@inertiajs/react';
import type { ButtonHTMLAttributes, ReactNode } from 'react';

type Variant = 'primary' | 'accent' | 'ghost';

interface ButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
    variant?: Variant;
    children: ReactNode;
}

const variants: Record<Variant, string> = {
    primary: 'bg-pickle-500 text-white hover:bg-pickle-600',
    accent: 'bg-energetic-500 text-white hover:bg-energetic-600',
    ghost: 'bg-white text-pickle-800 border border-hairline hover:bg-pickle-50',
};

/**
 * Shared call-to-action button. Orange (`accent`) is reserved for primary
 * booking actions so it keeps its meaning — see plan.md §3.
 */
export default function Button({ variant = 'primary', children, className = '', ...props }: ButtonProps) {
    return (
        <button
            className={`rounded-card px-6 py-3 font-semibold transition-colors disabled:cursor-not-allowed disabled:opacity-60 ${variants[variant]} ${className}`}
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
}

export function ButtonLink({ href, variant = 'primary', children, className = '' }: ButtonLinkProps) {
    return (
        <Link
            href={href}
            className={`inline-block rounded-card px-6 py-3 font-semibold transition-colors ${variants[variant]} ${className}`}
        >
            {children}
        </Link>
    );
}
