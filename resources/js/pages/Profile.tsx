import { Form, useForm, usePage } from '@inertiajs/react';
import PageLayout, { Section } from '@/layouts/PageLayout';
import Seo from '@/components/seo/Seo';
import { TextField } from '@/components/auth/AuthFields';
import type { SharedProps } from '@/types';

interface ProfileProps {
    user: {
        id: number;
        name: string;
        email: string;
        phone: string | null;
        skill_level: string | null;
    };
    mustVerifyEmail: boolean;
    status?: string;
}

const skillLevels = [
    { value: 'beginner', label: 'Beginner' },
    { value: 'intermediate', label: 'Intermediate' },
    { value: 'advanced', label: 'Advanced' },
    { value: 'competitive', label: 'Competitive' },
];

export default function Profile({ user, mustVerifyEmail, status }: ProfileProps) {
    const shared = usePage<SharedProps>().props;

    const form = useForm({
        name: user.name,
        email: user.email,
        phone: user.phone ?? '',
        skill_level: user.skill_level ?? 'beginner',
    });

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        form.put('/profile');
    };

    return (
        <PageLayout shared={shared}>
            <Seo title="Profile" description="Update your PicklePlay profile." noindex />

            <Section className="py-14">
                <h1 className="font-display text-mist-50 text-3xl sm:text-4xl">Your profile</h1>
                <p className="text-mist-400 mt-3">Manage how you appear across the platform.</p>

                {status === 'profile-updated' && (
                    <p
                        role="status"
                        className="border-lime-accent bg-night-850 text-mist-50 rounded-card mt-8 border-l-4 px-4 py-3 text-sm"
                    >
                        Profile saved.
                    </p>
                )}

                {mustVerifyEmail && !shared.auth.user?.email_verified_at && (
                    <div className="border-energetic-400 bg-night-850 text-mist-50 rounded-card mt-6 border-l-4 px-4 py-3 text-sm">
                        <p className="font-semibold">Email not verified.</p>
                        <Form action="/email/verification-notification" method="post" className="mt-2">
                            <button type="submit" className="text-energetic-300 font-semibold underline">
                                Resend the verification email
                            </button>
                        </Form>
                    </div>
                )}

                <ProfileForm form={form} onSubmit={submit} />
            </Section>
        </PageLayout>
    );
}
type ProfileFormData = ReturnType<typeof useForm<{ name: string; email: string; phone: string; skill_level: string }>>;

function ProfileForm({ form, onSubmit }: { form: ProfileFormData; onSubmit: (e: React.FormEvent) => void }) {
    return (
        <Form onSubmit={onSubmit} className="bg-night-850 border-hairline-dark mt-10 max-w-xl rounded-panel border p-7">
            <div className="space-y-5">
                <TextField
                    label="Full name"
                    name="name"
                    dark
                    autoComplete="name"
                    required
                    value={form.data.name}
                    onChange={(e) => form.setData('name', e.target.value)}
                    error={form.errors.name}
                />

                <TextField
                    label="Email"
                    name="email"
                    dark
                    type="email"
                    autoComplete="username"
                    required
                    value={form.data.email}
                    onChange={(e) => form.setData('email', e.target.value)}
                    error={form.errors.email}
                />

                <TextField
                    label="Mobile number"
                    name="phone"
                    dark
                    type="tel"
                    autoComplete="tel"
                    placeholder="+63 917 000 0000"
                    value={form.data.phone}
                    onChange={(e) => form.setData('phone', e.target.value)}
                    error={form.errors.phone}
                />

                <div>
                    <label htmlFor="skill_level" className="text-mist-200 block text-sm font-medium">
                        Playing level
                    </label>
                    <select
                        id="skill_level"
                        name="skill_level"
                        value={form.data.skill_level}
                        onChange={(e) => form.setData('skill_level', e.target.value)}
                        className="border-hairline-dark bg-night-950 text-mist-50 focus:border-lime-accent mt-1.5 block w-full rounded-card border px-4 py-2.5 text-sm focus:outline-none"
                    >
                        {skillLevels.map((level) => (
                            <option key={level.value} value={level.value}>
                                {level.label}
                            </option>
                        ))}
                    </select>
                    {form.errors.skill_level && (
                        <p className="text-energetic-300 mt-1.5 text-sm">{form.errors.skill_level}</p>
                    )}
                </div>
            </div>

            <button
                type="submit"
                disabled={form.processing}
                className="btn-pill btn-pill-primary mt-8 px-6 py-2.5 text-xs"
            >
                {form.processing ? 'Saving…' : 'Save changes'}
            </button>
        </Form>
    );
}
