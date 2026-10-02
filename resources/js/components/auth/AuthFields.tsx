import type { InputHTMLAttributes, ReactNode } from 'react';
import { cx } from '@/lib/format';

interface TextFieldProps extends InputHTMLAttributes<HTMLInputElement> {
    label: string;
    error?: string;
    /**
     * Renders the field for a dark surface. The auth screens keep the light
     * treatment (plan.md §3), so this is opt-in per usage rather than global.
     */
    dark?: boolean;
}

/** Labelled text input with accessible error wiring. */
export function TextField({ label, error, id, className, dark = false, ...props }: TextFieldProps) {
    const inputId = id ?? props.name;
    const errorId = `${inputId}-error`;

    return (
        <div>
            <label
                htmlFor={inputId}
                className={cx('block text-sm font-medium', dark ? 'text-mist-200' : 'text-pickle-900')}
            >
                {label}
            </label>
            <input
                id={inputId}
                aria-invalid={error ? 'true' : undefined}
                aria-describedby={error ? errorId : undefined}
                className={cx(
                    'mt-1.5 block w-full rounded-card border px-4 py-2.5 text-sm',
                    dark
                        ? 'bg-night-950 text-mist-50 placeholder:text-mist-500 focus:border-lime-accent focus:outline-none'
                        : 'focus:border-pickle-500 focus:ring-pickle-500',
                    error ? 'border-energetic-400' : dark ? 'border-hairline-dark' : 'border-hairline',
                    className,
                )}
                {...props}
            />
            {error && (
                <p id={errorId} className={cx('mt-1.5 text-sm', dark ? 'text-energetic-300' : 'text-energetic-600')}>
                    {error}
                </p>
            )}
        </div>
    );
}

interface AuthButtonProps {
    children: ReactNode;
    loading?: boolean;
}

/** Full-width submit button for auth forms. */
export function AuthButton({ children, loading = false }: AuthButtonProps) {
    return (
        <button
            type="submit"
            disabled={loading}
            className="bg-energetic-500 hover:bg-energetic-600 w-full rounded-card px-6 py-3 font-semibold text-white transition-colors disabled:cursor-not-allowed disabled:opacity-60"
        >
            {loading ? 'Please wait…' : children}
        </button>
    );
}
