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
