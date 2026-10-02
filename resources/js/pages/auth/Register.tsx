import { Form, Link, useForm, usePage } from '@inertiajs/react';
import AuthLayout from '@/layouts/AuthLayout';
import Seo from '@/components/seo/Seo';
import { AuthButton, TextField } from '@/components/auth/AuthFields';
import { withBasePath } from '@/lib/format';

export default function Register() {
    const { errors } = usePage<{ errors: Record<string, string> }>().props;

    const form = useForm({
        name: '',
        email: '',
        phone: '',
        password: '',
        password_confirmation: '',
    });

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        form.post(withBasePath('/register'), { onFinish: () => form.reset('password', 'password_confirmation') });
    };

    return (
        <AuthLayout
            title="Create your account"
            subtitle="Book courts, join games and stay connected with your local pickleball community."
        >
            <Seo title="Register" description="Create a PicklePlay account to book pickleball courts." noindex />

            <Form onSubmit={submit} className="space-y-5">
                <TextField
                    label="Full name"
                    name="name"
                    autoComplete="name"
                    required
                    autoFocus
                    value={form.data.name}
                    onChange={(e) => form.setData('name', e.target.value)}
                    error={errors.name}
                />

                <TextField
                    label="Email"
                    name="email"
                    type="email"
                    autoComplete="username"
                    required
                    value={form.data.email}
                    onChange={(e) => form.setData('email', e.target.value)}
                    error={errors.email}
                />

                <TextField
                    label="Mobile number (optional)"
                    name="phone"
                    type="tel"
                    autoComplete="tel"
                    placeholder="+63 917 000 0000"
                    value={form.data.phone}
                    onChange={(e) => form.setData('phone', e.target.value)}
                    error={errors.phone}
                />

                <TextField
                    label="Password"
                    name="password"
                    type="password"
                    autoComplete="new-password"
                    required
                    value={form.data.password}
                    onChange={(e) => form.setData('password', e.target.value)}
                    error={errors.password}
                />

                <TextField
                    label="Confirm password"
                    name="password_confirmation"
                    type="password"
                    autoComplete="new-password"
                    required
                    value={form.data.password_confirmation}
                    onChange={(e) => form.setData('password_confirmation', e.target.value)}
                    error={errors.password_confirmation}
                />

                <AuthButton loading={form.processing}>Create account</AuthButton>
            </Form>

            <p className="text-slate mt-6 text-center text-sm">
                Already have an account?{' '}
                <Link
                    href={withBasePath('/login')}
                    className="text-pickle-700 hover:text-pickle-900 font-semibold transition-colors"
                >
                    Log in
                </Link>
            </p>
        </AuthLayout>
    );
}
