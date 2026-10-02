/**
 * Props shared with every Inertia response by HandleInertiaRequests.
 * Mirror any change to app/Http/Middleware/HandleInertiaRequests.php.
 */
export interface NavItem {
    label: string;
    href: string;
}

export interface SharedProps {
    brand: {
        name: string;
        tagline: string;
        headline: string;
        description: string;
    };
    nav: NavItem[];
    coverage: string[];
    social: {
        facebook?: string;
        instagram?: string;
    };
    locale: {
        currency: string;
        currencySymbol: string;
        timezone: string;
    };
    contact: {
        email: string;
        phone: string;
    };
    flash: {
        success?: string;
        error?: string;
    };
    auth: {
        user: {
            id: number;
            name: string;
            email: string;
        } | null;
    };
    /**
     * Inertia's PageProps constraint requires an index signature for any extra
     * props a page may receive alongside the shared ones.
     */
    [key: string]: unknown;
}
