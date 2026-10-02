import { usePage } from '@inertiajs/react';
import { Mail, MapPin, Phone } from 'lucide-react';
import SectionHeading from '@/components/marketing/SectionHeading';
import Seo from '@/components/seo/Seo';
import PageLayout, { Section } from '@/layouts/PageLayout';
import type { SharedProps } from '@/types';

interface ContactProps {
    contact: { title: string; description: string };
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

export default function Contact({ contact, details }: ContactProps) {
    const shared = usePage<SharedProps>().props;
    const { address } = details;

    return (
        <PageLayout shared={shared}>
            <Seo />

            <Section className="py-14">
                <SectionHeading
                    eyebrow="Contact"
                    title={contact.title}
                    description={contact.description}
                    align="center"
                />

                <div className="mx-auto mt-12 grid max-w-4xl gap-5 sm:grid-cols-3">
                    <div className="border-hairline rounded-card border bg-white p-6 text-center">
                        <span className="bg-pickle-50 text-pickle-700 mx-auto grid h-12 w-12 place-items-center rounded-card">
                            <Mail size={20} aria-hidden />
                        </span>
                        <h2 className="mt-4 font-semibold text-pickle-900">Email</h2>
                        <a
                            href={`mailto:${details.email}`}
                            className="text-slate hover:text-pickle-700 mt-1 block text-sm break-words transition-colors"
                        >
                            {details.email}
                        </a>
                    </div>

                    <div className="border-hairline rounded-card border bg-white p-6 text-center">
                        <span className="bg-pickle-50 text-pickle-700 mx-auto grid h-12 w-12 place-items-center rounded-card">
                            <Phone size={20} aria-hidden />
                        </span>
                        <h2 className="mt-4 font-semibold text-pickle-900">Phone</h2>
                        <a
                            href={`tel:${details.phone.replace(/\s/g, '')}`}
                            className="text-slate hover:text-pickle-700 mt-1 block text-sm transition-colors"
                        >
                            {details.phone}
                        </a>
                    </div>

                    <div className="border-hairline rounded-card border bg-white p-6 text-center">
                        <span className="bg-pickle-50 text-pickle-700 mx-auto grid h-12 w-12 place-items-center rounded-card">
                            <MapPin size={20} aria-hidden />
                        </span>
                        <h2 className="mt-4 font-semibold text-pickle-900">Office</h2>
                        <address className="text-slate mt-1 text-sm not-italic">
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
