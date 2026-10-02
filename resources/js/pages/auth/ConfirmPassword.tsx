import { Form, useForm } from '@inertiajs/react';
import AuthLayout from '@/layouts/AuthLayout';
import Seo from '@/components/seo/Seo';
import { AuthButton, TextField } from '@/components/auth/AuthFields';

export default function ConfirmPassword() {
    const form = useForm({ password: '' });

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        form.post('/user/confirm-password', { onFinish: () => form.reset() });
    };

    return (
        <AuthLayout
            title="Confirm your password"
            subtitle="This is a protected area. Please confirm your password to continue."
        >
            <Seo title="Confirm password" description="Confirm your password to continue." noindex />

            <Form onSubmit={submit} className="space-y-5">
                <TextField
                    label="Password"
                    name="password"
                    type="password"
                    autoComplete="current-password"
                    required
                    autoFocus
                    value={form.data.password}
                    onChange={(e) => form.setData('password', e.target.value)}
                    error={form.errors.password}
                />

                <AuthButton loading={form.processing}>Confirm</AuthButton>
            </Form>
        </AuthLayout>
    );
}
