import { Link, useForm, usePage } from '@inertiajs/react';
import AuthLayout from '@/layouts/AuthLayout';
import Seo from '@/components/seo/Seo';
import { AuthButton, TextField } from '@/components/auth/AuthFields';
import { withBasePath } from '@/lib/format';

interface LoginProps {
    canResetPassword?: boolean;
}

/**
 * A plain <form> is deliberate, not an oversight.
 *
 * Inertia's <Form> renders its own onSubmit *after* spreading props:
 *
 *     createElement('form', { ...props, onSubmit: (e) => { e.preventDefault(); ... } })
 *
 * so an onSubmit passed to it is discarded. The form then fell back to native
 * browser submission -- action="" and method="get" -- which put the password in
 * the URL and re-rendered /login with no error shown. We call preventDefault()
 * ourselves, so a plain <form> is correct and keeps useForm's processing state
 * and error wiring exactly as they were.
 */
export default function Login({ canResetPassword = true }: LoginProps) {
    const { errors, status } = usePage<{ errors: Record<string, string>; status?: string }>().props;

    const form = useForm({ email: '', password: '', remember: false });

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        form.post(withBasePath('/login'), { onFinish: () => form.reset('password') });
    };

    return (
        <AuthLayout title="Welcome back" subtitle="Log in to book courts, manage your facility or join events.">
            <Seo title="Log in" description="Log in to your PicklePlay account." noindex />

            {status === 'verification-link-sent' && (
                <p
                    role="status"
                    className="border-pickle-200 bg-pickle-50 text-pickle-800 rounded-card mb-5 border px-4 py-3 text-sm"
                >
                    A fresh verification link has been sent to your email address.
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

                <div>
                    <div className="flex items-center justify-between">
                        <TextField
                            label="Password"
                            name="password"
                            type="password"
                            autoComplete="current-password"
                            required
                            className="flex-1"
                            value={form.data.password}
                            onChange={(e) => form.setData('password', e.target.value)}
                            error={errors.password}
                        />
                    </div>

                    {canResetPassword && (
                        <Link
                            href={withBasePath('/forgot-password')}
                            className="text-pickle-700 hover:text-pickle-900 mt-2 inline-block text-sm transition-colors"
                        >
                            Forgot your password?
                        </Link>
                    )}
                </div>

                <label className="flex items-center gap-2 text-sm text-slate">
                    <input
                        type="checkbox"
                        name="remember"
                        checked={form.data.remember}
                        onChange={(e) => form.setData('remember', e.target.checked)}
                        className="border-hairline text-pickle-500 focus:ring-pickle-500 rounded"
                    />
                    Remember me
                </label>

                <AuthButton loading={form.processing}>Log in</AuthButton>
            </form>

            <p className="text-slate mt-6 text-center text-sm">
                New here?{' '}
                <Link
                    href={withBasePath('/register')}
                    className="text-pickle-700 hover:text-pickle-900 font-semibold transition-colors"
                >
                    Create an account
                </Link>
            </p>
        </AuthLayout>
    );
}
