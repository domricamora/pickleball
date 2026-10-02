/**
 * Formats a numeric amount as Philippine peso, e.g. 1250 -> "₱1,250.00".
 * Always pass pesos (not centavos) — see plan.md §5.
 */
export function peso(amount: number, options: { decimals?: boolean } = {}): string {
    const { decimals = true } = options;

    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: decimals ? 2 : 0,
        maximumFractionDigits: decimals ? 2 : 0,
    }).format(amount);
}

/**
 * Formats an ISO date as a readable Manila-local date, e.g. "Feb 3, 2026".
 * Time is always rendered in Asia/Manila regardless of the visitor's timezone
 * (plan.md §32) so court times never shift for players abroad or on a device
 * set to another zone.
 */
export function formatDate(iso: string): string {
    return new Intl.DateTimeFormat('en-PH', {
        timeZone: 'Asia/Manila',
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    }).format(new Date(iso));
}

/** Formats a 24-hour "HH:mm" time as a 12-hour Manila time, e.g. "6:30 PM". */
export function formatTime(time: string): string {
    const [hours, minutes] = time.split(':').map(Number);

    return new Intl.DateTimeFormat('en-PH', {
        timeZone: 'Asia/Manila',
        hour: 'numeric',
        minute: '2-digit',
        hour12: true,
    }).format(new Date(Date.UTC(2026, 0, 1, hours ?? 0, minutes ?? 0)));
}

/** Joins conditional class names, dropping falsy values. */
export function cx(...classes: Array<string | false | null | undefined>): string {
    return classes.filter(Boolean).join(' ');
}

/**
 * The mount point the app is served from, e.g. "/p/public" under WAMP or ""
 * when the document root already points at public/.
 *
 * Shared by the server as `basePath` (see HandleInertiaRequests). Cached
 * because it is stable for the life of the page.
 */
let basePath: string | null = null;

export function setBasePath(value: string | null | undefined): void {
    basePath = (value ?? '').replace(/\/+$/, '');
}

/** The current mount point, normalised to have no trailing slash. */
export function getBasePath(): string {
    return basePath ?? '';
}

/**
 * Prefixes a root-relative path with the mount point.
 *
 * Links are written as "/facilities" because that is the route Laravel
 * defines. The browser resolves a leading-slash URL against the domain root,
 * so on a subdirectory mount "/facilities" leaves the app entirely and lands
 * on the server root -- which is what makes "Facilities" navigate away from
 * the site. Prefixing keeps the same route string working at any mount point.
 *
 * Absolute URLs, external links, anchors and query/hash suffixes pass through
 * untouched, so this is safe to apply to any href.
 */
export function withBasePath(href: string): string {
    if (!href.startsWith('/') || href.startsWith('//')) {
        return href;
    }

    // Already prefixed (absolute URL from url(), or a resolved asset path).
    if (basePath && href.startsWith(`${basePath}/`)) {
        return href;
    }

    return `${basePath ?? ''}${href}`;
}

/**
 * Resolves a path under `public/` -- media, images, downloads.
 *
 * Same mount-point problem as withBasePath(), and the same fix: a
 * root-relative "/media/courts.webp" 404s under a subdirectory mount because
 * the browser asks the domain root for it. Everything shipped from public/
 * goes through here so that cannot be missed again.
 *
 * Absolute URLs and data URIs pass through untouched.
 */
export function assetUrl(path: string): string {
    if (/^(https?:)?\/\//.test(path) || path.startsWith('data:')) {
        return path;
    }

    return withBasePath(path.startsWith('/') ? path : `/${path}`);
}
