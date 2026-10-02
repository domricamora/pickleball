import { Head } from '@inertiajs/react';
import type { ReactNode } from 'react';

/**
 * Shared props every Inertia page receives. Keep tenant context here so pages
 * never reach for global state directly.
 */
export interface SharedProps {
    appName: string;
    flash?: {
        success?: string;
        error?: string;
    } | null;
    [key: string]: unknown;
}

interface AppLayoutProps {
    children: ReactNode;
    title?: string;
}

export default function AppLayout({ children, title }: AppLayoutProps) {
    return (
        <div className="flex min-h-screen flex-col">
            <Head title={title} />

            <header className="border-hairline bg-white/80 sticky top-0 z-20 border-b backdrop-blur">
                <div className="mx-auto flex w-full max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                    <span className="font-display text-xl font-extrabold text-pickle-900">PicklePlay</span>
                    <nav className="flex items-center gap-1 text-sm font-medium text-slate">
                        <span className="rounded-full bg-pickle-50 px-4 py-2 text-pickle-700">Foundation</span>
                    </nav>
                </div>
            </header>

            <main className="mx-auto w-full max-w-7xl flex-1 px-4 py-10 sm:px-6 lg:px-8">{children}</main>

            <footer className="border-hairline border-t">
                <div className="mx-auto w-full max-w-7xl px-4 py-6 text-sm text-slate sm:px-6 lg:px-8">
                    Philippine Pickleball Courts Platform
                </div>
            </footer>
        </div>
    );
}
