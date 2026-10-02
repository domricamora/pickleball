/**
 * Props shared with every Inertia response by HandleInertiaRequests.
 * Mirror any change to app/Http/Middleware/HandleInertiaRequests.php.
 */
export interface NavItem {
    label: string;
    href: string;
}

export interface SharedProps {
    /**
     * Where the app is mounted, e.g. "/p/public" under WAMP, "" at the domain
     * root. Prefixed onto root-relative links by withBasePath().
     */
    basePath: string;
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
            phone: string | null;
            email_verified_at: string | null;
            is_active: boolean;
        } | null;
        role: string | null;
        permissions: string[];
        isPlatformStaff: boolean;
        organizationId: number | null;
        branchId: number | null;
    };
    /**
     * Every permission name in the system, granted in full to platform staff.
     */
    permissions: string[];
    /**
     * Inertia's PageProps constraint requires an index signature for any extra
     * props a page may receive alongside the shared ones.
     */
    [key: string]: unknown;
}

/** True when the signed-in user holds the given permission. */
export function can(shared: SharedProps, permission: string): boolean {
    return shared.permissions.includes(permission);
}
