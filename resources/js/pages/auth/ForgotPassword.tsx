import { Link, useForm, usePage } from '@inertiajs/react';
import AuthLayout from '@/layouts/AuthLayout';
import Seo from '@/components/seo/Seo';
import { AuthButton, TextField } from '@/components/auth/AuthFields';
import { withBasePath } from '@/lib/format';

export default function ForgotPassword() {
    const { errors, status } = usePage<{ errors: Record<string, string>; status?: string }>().props;

    const form = useForm({ email: '' });

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        form.post(withBasePath('/forgot-password'));
    };

    return (
        <AuthLayout title="Reset your password" subtitle="Enter your email address and we will send you a reset link.">
            <Seo title="Forgot password" description="Reset your PicklePlay password." noindex />

            {status && (
                <p
                    role="status"
                    className="border-pickle-200 bg-pickle-50 text-pickle-800 rounded-card mb-5 border px-4 py-3 text-sm"
                >
                    If that email address exists in our records, a password reset link is on its way.
                </p>
            )}

            <form onSubmit={submit} className="space-y-5">
                <TextField
                    label="Email"
                    name="email"
                    type="email"
                    autoComplete="username"
                    required
                    autoFocus
                    value={form.data.email}
                    onChange={(e) => form.setData('email', e.target.value)}
                    error={errors.email}
                />

                <AuthButton loading={form.processing}>Email password reset link</AuthButton>
            </form>

            <p className="text-slate mt-6 text-center text-sm">
                <Link
                    href={withBasePath('/login')}
                    className="text-pickle-700 hover:text-pickle-900 font-semibold transition-colors"
                >
                    Back to log in
                </Link>
            </p>
        </AuthLayout>
    );
}
