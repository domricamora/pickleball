/**
 * Small labelled form controls shared by the admin forms.
 */

interface FieldShellProps {
    id: string;
    label: string;
    error?: string;
    hint?: string;
    children: React.ReactNode;
}

function FieldShell({ id, label, error, hint, children }: FieldShellProps) {
    return (
        <div>
            <label htmlFor={id} className="text-pickle-900 block text-sm font-medium">
                {label}
            </label>
            {children}
            {hint && !error && <p className="text-slate mt-1.5 text-xs">{hint}</p>}
            {error && (
                <p id={`${id}-error`} role="alert" className="text-energetic-600 mt-1.5 text-sm">
                    {error}
                </p>
            )}
        </div>
    );
}

const controlClass = (error?: string) =>
    `border-hairline focus:border-pickle-500 focus:ring-pickle-500 mt-1.5 block w-full rounded-card border px-4 py-2.5 text-sm ${error ? 'border-energetic-400' : ''}`;

interface TextFieldProps {
    id: string;
    label: string;
    value: string | number | null;
    onChange: (value: string) => void;
    error?: string;
    hint?: string;
    type?: string;
    placeholder?: string;
    min?: number;
    max?: number;
    required?: boolean;
}

export function TextField({ id, label, value, onChange, error, hint, ...rest }: TextFieldProps) {
    return (
        <FieldShell id={id} label={label} error={error} hint={hint}>
            <input
                id={id}
                name={id}
                type={rest.type ?? 'text'}
                value={value ?? ''}
                onChange={(e) => onChange(e.target.value)}
                placeholder={rest.placeholder}
                min={rest.min}
                max={rest.max}
                required={rest.required}
                aria-invalid={error ? 'true' : undefined}
                aria-describedby={error ? `${id}-error` : undefined}
                className={controlClass(error)}
            />
        </FieldShell>
    );
}

interface SelectFieldProps {
    id: string;
    label: string;
    value: string | number;
    onChange: (value: string) => void;
    options: Array<{ value: string | number; label: string }>;
    error?: string;
    hint?: string;
    required?: boolean;
}

export function SelectField({ id, label, value, onChange, options, error, hint, required }: SelectFieldProps) {
    return (
        <FieldShell id={id} label={label} error={error} hint={hint}>
            <select
                id={id}
                name={id}
                value={value ?? ''}
                required={required}
                onChange={(e) => onChange(e.target.value)}
                className={controlClass(error)}
            >
                {options.map((option) => (
                    <option key={option.value} value={option.value}>
                        {option.label}
                    </option>
                ))}
            </select>
        </FieldShell>
    );
}

interface TextAreaProps {
    id: string;
    label: string;
    value: string | null;
    onChange: (value: string) => void;
    error?: string;
    hint?: string;
}

export function TextAreaField({ id, label, value, onChange, error, hint }: TextAreaProps) {
    return (
        <FieldShell id={id} label={label} error={error} hint={hint}>
            <textarea
                id={id}
                name={id}
                rows={3}
                value={value ?? ''}
                onChange={(e) => onChange(e.target.value)}
                className={controlClass(error)}
            />
        </FieldShell>
    );
}

interface SubmitRowProps {
    processing: boolean;
    submitLabel: string;
    cancelHref: string;
}

/** Submit + cancel pair, used at the foot of every admin form. */
export function SubmitRow({ processing, submitLabel, cancelHref }: SubmitRowProps) {
    return (
        <div className="mt-7 flex items-center gap-3">
            <button
                type="submit"
                disabled={processing}
                className="bg-energetic-500 hover:bg-energetic-600 rounded-card px-6 py-2.5 font-semibold text-white transition-colors disabled:opacity-60"
            >
                {processing ? 'Saving…' : submitLabel}
            </button>
            <a href={cancelHref} className="text-slate hover:text-pickle-800 text-sm font-semibold transition-colors">
                Cancel
            </a>
        </div>
    );
}
