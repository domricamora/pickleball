import { Form, Link, useForm, usePage } from '@inertiajs/react';
import { useEffect } from 'react';
import AuthLayout from '@/layouts/AuthLayout';
import Seo from '@/components/seo/Seo';
import { AuthButton } from '@/components/auth/AuthFields';

interface VerifyEmailProps {
    status?: string;
}

export default function VerifyEmail({ status }: VerifyEmailProps) {
    const { errors } = usePage<{ errors: Record<string, string> }>().props;

    const form = useForm({});

    // Fortify sends the verification link automatically on arrival.
    useEffect(() => {
        if (status === undefined) {
            form.post('/email/verification-notification');
        }
        // Intentionally runs once on mount.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    return (
        <AuthLayout title="Verify your email" subtitle="Confirm your address to finish setting up your account.">
            <Seo title="Verify email" description="Confirm your email address." noindex />

            {status === 'verified' ? (
                <>
                    <p
                        role="status"
                        className="border-pickle-200 bg-pickle-50 text-pickle-800 rounded-card border px-4 py-3 text-sm"
                    >
                        Your email address is verified.
                    </p>
                    <Link
                        href="/dashboard"
                        className="bg-energetic-500 hover:bg-energetic-600 mt-5 block rounded-card px-6 py-3 text-center font-semibold text-white transition-colors"
                    >
                        Continue to dashboard
                    </Link>
                </>
            ) : (
                <>
                    {errors.email && (
                        <p
                            role="alert"
                            className="border-energetic-200 bg-energetic-50 text-energetic-800 rounded-card mb-5 border px-4 py-3 text-sm"
                        >
                            {errors.email}
                        </p>
                    )}

                    <p className="text-slate mb-5 text-sm">
                        We sent a verification link to your email address. Click it to confirm your account.
                    </p>

                    <Form
                        onSubmit={(event) => {
                            event.preventDefault();
                            form.post('/email/verification-notification');
                        }}
                    >
                        <AuthButton loading={form.processing}>Resend verification email</AuthButton>
                    </Form>

                    <Link
                        href="/logout"
                        method="post"
                        as="button"
                        className="text-slate mt-6 block w-full text-center text-sm transition-colors hover:text-pickle-700"
                    >
                        Log out
                    </Link>
                </>
            )}
        </AuthLayout>
    );
}
