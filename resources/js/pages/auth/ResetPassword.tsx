import { Form, useForm } from '@inertiajs/react';
import AuthLayout from '@/layouts/AuthLayout';
import Seo from '@/components/seo/Seo';
import { AuthButton, TextField } from '@/components/auth/AuthFields';

interface ResetPasswordProps {
    email: string;
    token: string;
}

export default function ResetPassword({ email, token }: ResetPasswordProps) {
    const form = useForm({
        token,
        email,
        password: '',
        password_confirmation: '',
    });

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        form.post('/reset-password', { onFinish: () => form.reset('password', 'password_confirmation') });
    };

    return (
        <AuthLayout title="Choose a new password" subtitle="Pick something strong you have not used before.">
            <Seo title="Reset password" description="Choose a new password for your PicklePlay account." noindex />

            <Form onSubmit={submit} className="space-y-5">
                <TextField
                    label="Email"
                    name="email"
                    type="email"
                    autoComplete="username"
                    required
                    readOnly
                    value={form.data.email}
                    onChange={(e) => form.setData('email', e.target.value)}
                    error={form.errors.email}
                />

                <TextField
                    label="New password"
                    name="password"
                    type="password"
                    autoComplete="new-password"
                    required
                    autoFocus
                    value={form.data.password}
                    onChange={(e) => form.setData('password', e.target.value)}
                    error={form.errors.password}
                />

                <TextField
                    label="Confirm new password"
                    name="password_confirmation"
                    type="password"
                    autoComplete="new-password"
                    required
                    value={form.data.password_confirmation}
                    onChange={(e) => form.setData('password_confirmation', e.target.value)}
                    error={form.errors.password_confirmation}
                />

                <AuthButton loading={form.processing}>Reset password</AuthButton>
            </Form>
        </AuthLayout>
    );
}
