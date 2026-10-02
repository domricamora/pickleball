import { Form, Link, useForm, usePage } from '@inertiajs/react';
import AuthLayout from '@/layouts/AuthLayout';
import Seo from '@/components/seo/Seo';
import { AuthButton, TextField } from '@/components/auth/AuthFields';
import { withBasePath } from '@/lib/format';

interface ChallengeProps {
    /** Fortify requests a recovery code when the device is lost. */
    recovery?: boolean;
}

export default function TwoFactorChallenge({ recovery = false }: ChallengeProps) {
    const { errors } = usePage<{ errors: Record<string, string> }>().props;

    const form = useForm({ code: '', recovery_code: '' });
    const useRecovery = recovery || form.data.recovery_code !== '';

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        form.post(withBasePath('/two-factor-challenge'));
    };

    return (
        <AuthLayout
            title="Two-factor authentication"
            subtitle="Enter the code from your authenticator app to continue."
        >
            <Seo title="Two-factor challenge" description="Enter your two-factor code." noindex />

            {errors.code && (
                <p
                    role="alert"
                    className="border-energetic-200 bg-energetic-50 text-energetic-800 rounded-card mb-5 border px-4 py-3 text-sm"
                >
                    {errors.code}
                </p>
            )}

            <Form onSubmit={submit} className="space-y-5">
                {useRecovery ? (
                    <TextField
                        label="Recovery code"
                        name="recovery_code"
                        autoComplete="one-time-code"
                        required
                        autoFocus
                        value={form.data.recovery_code}
                        onChange={(e) => form.setData('recovery_code', e.target.value)}
                        error={form.errors.recovery_code}
                    />
                ) : (
                    <TextField
                        label="Authentication code"
                        name="code"
                        inputMode="numeric"
                        autoComplete="one-time-code"
                        required
                        autoFocus
                        value={form.data.code}
                        onChange={(e) => form.setData('code', e.target.value)}
                        error={form.errors.code}
                    />
                )}

                <AuthButton loading={form.processing}>Continue</AuthButton>
            </Form>

            <div className="mt-6 text-center text-sm">
                <button
                    type="button"
                    onClick={() => form.setData('recovery_code', useRecovery ? '' : ' ')}
                    className="text-pickle-700 hover:text-pickle-900 font-semibold transition-colors"
                >
                    {useRecovery ? 'Use an authentication code' : 'Use a recovery code'}
                </button>
                <span className="text-slate mx-2">·</span>
                <Link
                    href={withBasePath('/logout')}
                    method="post"
                    as="button"
                    className="text-slate hover:text-pickle-700 transition-colors"
                >
                    Log out
                </Link>
            </div>
        </AuthLayout>
    );
}
