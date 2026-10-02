import { usePage } from '@inertiajs/react';
import { Mail, MapPin, Phone } from 'lucide-react';
import Seo from '@/components/seo/Seo';
import PageLayout, { PageHero, Section } from '@/layouts/PageLayout';
import type { SharedProps } from '@/types';

interface ContactProps {
    contactPage: { title: string; description: string };
    details: {
        email: string;
        phone: string;
        address: {
            street: string;
            barangay: string;
            city: string;
            province: string;
            region: string;
            postal_code: string;
            country: string;
        };
    };
}

export default function Contact({ contactPage, details }: ContactProps) {
    const shared = usePage<SharedProps>().props;
    const { address } = details;

    return (
        <PageLayout shared={shared}>
            <Seo />

            <PageHero
                eyebrow="Contact"
                title={contactPage.title}
                description={contactPage.description}
                image="/media/courts-hall.webp"
                alt="A row of indoor pickleball courts behind black perimeter fencing"
            />

            <Section className="py-20 sm:py-24">
                <div className="mx-auto grid max-w-4xl gap-5 sm:grid-cols-3">
                    <div className="bg-night-850 border-hairline-dark rounded-card border p-6 text-center">
                        <span className="bg-night-800 text-lime-accent mx-auto grid h-12 w-12 place-items-center rounded-pill">
                            <Mail size={20} aria-hidden />
                        </span>
                        <h2 className="font-display-plain text-mist-50 mt-5 tracking-wide uppercase">Email</h2>
                        <a
                            href={`mailto:${details.email}`}
                            className="text-mist-400 hover:text-lime-accent mt-1 block text-sm break-words transition-colors"
                        >
                            {details.email}
                        </a>
                    </div>

                    <div className="bg-night-850 border-hairline-dark rounded-card border p-6 text-center">
                        <span className="bg-night-800 text-lime-accent mx-auto grid h-12 w-12 place-items-center rounded-pill">
                            <Phone size={20} aria-hidden />
                        </span>
                        <h2 className="font-display-plain text-mist-50 mt-5 tracking-wide uppercase">Phone</h2>
                        <a
                            href={`tel:${details.phone.replace(/\s/g, '')}`}
                            className="text-mist-400 hover:text-lime-accent mt-1 block text-sm transition-colors"
                        >
                            {details.phone}
                        </a>
                    </div>

                    <div className="bg-night-850 border-hairline-dark rounded-card border p-6 text-center">
                        <span className="bg-night-800 text-lime-accent mx-auto grid h-12 w-12 place-items-center rounded-pill">
                            <MapPin size={20} aria-hidden />
                        </span>
                        <h2 className="font-display-plain text-mist-50 mt-5 tracking-wide uppercase">Office</h2>
                        <address className="text-mist-400 mt-1 text-sm not-italic">
                            {address.street}
                            <br />
                            {address.barangay}, {address.city}
                            <br />
                            {address.province}, {address.postal_code}
                        </address>
                    </div>
                </div>
            </Section>
        </PageLayout>
    );
}
