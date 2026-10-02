import { Link } from '@inertiajs/react';
import AdminLayout from '@/layouts/AdminLayout';
import Seo from '@/components/seo/Seo';
import { withBasePath } from '@/lib/format';

export interface AdminButtonProps {
    href: string;
    children: string;
    variant?: 'primary' | 'secondary' | 'danger';
}

const variants = {
    primary: 'bg-energetic-500 hover:bg-energetic-600 text-white',
    secondary: 'border-hairline bg-white text-pickle-800 hover:bg-pickle-50 border',
    danger: 'border-energetic-200 bg-white text-energetic-700 hover:bg-energetic-50 border',
};

/** Shared action button for the admin tables. */
export function AdminButton({ href, children, variant = 'secondary' }: AdminButtonProps) {
    return (
        <Link
            href={withBasePath(href)}
            className={`rounded-card px-4 py-2 text-sm font-semibold transition-colors ${variants[variant]}`}
        >
            {children}
        </Link>
    );
}

interface StatusBadgeProps {
    label: string;
    className?: string;
}

/** Pill badge used for statuses across the admin UI. */
export function StatusBadge({ label, className = '' }: StatusBadgeProps) {
    return (
        <span
            className={`rounded-full px-3 py-1 text-xs font-bold tracking-wide uppercase ${className || 'bg-slate-100 text-slate-700'}`}
        >
            {label}
        </span>
    );
}

/** Shared page shell for the admin area. */
export function AdminPage({
    title,
    subtitle,
    actions,
    children,
}: {
    title: string;
    subtitle?: string;
    actions?: React.ReactNode;
    children: React.ReactNode;
}) {
    return (
        <AdminLayout title={title} subtitle={subtitle} actions={actions}>
            <Seo title={`${title} — Admin`} description="Back office." noindex />
            {children}
        </AdminLayout>
    );
}
