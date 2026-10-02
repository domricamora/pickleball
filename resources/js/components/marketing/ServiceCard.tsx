import type { ReactNode } from 'react';
import { ButtonLink } from '@/components/ui/Button';

export interface Service {
    icon: ReactNode;
    name: string;
    /** Short price line, e.g. "From ₱350 / hour". */
    price: string;
    description: string;
    ctaLabel: string;
    ctaHref: string;
}

interface ServiceCardProps {
    service: Service;
}

/**
 * The three-up "how to play" cards from the reference design: open play, court
 * rental and group or event booking.
 */
export default function ServiceCard({ service }: ServiceCardProps) {
    return (
        <article className="bg-night-850 border-hairline-dark hover:border-night-600 flex h-full flex-col rounded-card border p-6 transition-colors sm:p-7">
            <span className="text-lime-accent" aria-hidden>
                {service.icon}
            </span>

            <h3 className="font-display-plain text-mist-50 mt-5 text-lg tracking-wide">{service.name}</h3>
            <p className="text-mist-300 mt-1 text-sm">{service.price}</p>
            <p className="text-mist-400 mt-3 flex-1 text-sm leading-relaxed">{service.description}</p>

            <ButtonLink href={service.ctaHref} variant="pill" arrow className="mt-6 self-start px-6 py-2.5 text-xs">
                {service.ctaLabel}
            </ButtonLink>
        </article>
    );
}
